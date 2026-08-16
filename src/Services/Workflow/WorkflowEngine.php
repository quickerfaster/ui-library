<?php

namespace QuickerFaster\UILibrary\Services\Workflow;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowApproved;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowRecalled;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowRejected;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowSubmitted;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Models\WorkflowAction;
use QuickerFaster\UILibrary\Models\WorkflowDefinition;
use QuickerFaster\UILibrary\Models\WorkflowDefinitionStep;
use QuickerFaster\UILibrary\Models\WorkflowStep;
use QuickerFaster\UILibrary\Services\Approvals\ApprovalGuard;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;

class WorkflowEngine
{
    public function __construct(
        protected NotificationService $notifications,
        protected ApprovalGuard $guard,
        protected ApproverResolver $approvers,
    ) {}

    /**
     * Start a new workflow for the given workflowable entity.
     */
    public function start(Workflowable $entity, array $context = []): Workflow
    {
        $definitionKey = $entity->getWorkflowDefinitionKey();
        $definition = $this->getDefinition($definitionKey);

        if (!$definition) {
            throw new \InvalidArgumentException("Workflow definition '{$definitionKey}' not found.");
        }

        // Prevent duplicate active workflows (Bug 7).
        if ($this->hasActiveWorkflow($entity)) {
            throw new \RuntimeException("An active workflow already exists for this entity.");
        }

        // Authorize the submitter against the initiator tier (Bug 1).
        $initiatorIds = $definition['initiators'] ?? [];
        if ($initiatorIds !== []) {
            $user = Auth::user();
            $workspaceId = $context['workspace_id'] ?? null;

            if (! $this->guard->canSubmit($user, $initiatorIds, $workspaceId !== null ? (string) $workspaceId : null)) {
                throw new AuthorizationException('You are not authorized to submit this workflow.');
            }
        }

        $workflow = DB::transaction(function () use ($entity, $definitionKey, $definition, $context) {
            $workflow = Workflow::create([
                'workflowable_type' => get_class($entity),
                'workflowable_id' => $entity->getWorkflowableId(),
                'definition_key' => $definitionKey,
                'status' => 'pending',
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'context' => array_merge($entity->getWorkflowContext(), $context),
            ]);

            // Create runtime steps ONLY for review / authorizer tiers (Bug 2).
            // Initiator steps are not persisted as WorkflowStep rows — they
            // are used solely for submit-time authorization above.
            $sequence = 1;
            foreach ($definition['steps'] as $stepConfig) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'name' => $stepConfig['name'],
                    'sequence' => $sequence++,
                    'step_type' => $stepConfig['step_type'] ?? 'approval',
                    'approval_mode' => $stepConfig['approval_mode'] ?? 'any',
                    'roles' => $stepConfig['roles'] ?? [],
                    'status' => 'pending',
                ]);
            }

            // Auto-approve: a workflow definition with no review or authorizer
            // steps has no approval gates. The workflow is approved immediately
            // on submission so it doesn't remain stuck in "pending" forever.
            if ($workflow->steps()->count() === 0) {
                $workflow->update([
                    'status' => 'approved',
                    'completed_at' => now(),
                ]);

                $this->logAction($workflow, null, 'completed');

                return $workflow;
            }

            // Set current step to first step
            $firstStep = $workflow->steps()->orderBy('sequence')->first();
            $workflow->current_step = $firstStep?->id;
            $workflow->save();

            // Log action
            $this->logAction($workflow, null, 'submitted');

            return $workflow;
        });

        // If the workflow was auto-approved (no steps), fire the approved
        // event instead of submitted and skip notification resolution.
        if ($workflow->status === 'approved') {
            event(new WorkflowApproved($workflow, null, Auth::id(), true));

            return $workflow;
        }

        event(new WorkflowSubmitted($workflow, $entity));

        $workspaceId = $this->resolveWorkspaceId($workflow);
        $this->notifyTransition(
            $workflow,
            'submitted',
            $this->resolveStepRecipientIds($workflow->currentStep, $workspaceId),
            ['workflowable_type' => $workflow->workflowable_type],
        );

        return $workflow;
    }

    /**
     * Approve the current step and advance the workflow.
     *
     * Supports two resolution modes:
     *
     * - **any** (default): A single approval marks the step approved and
     *   advances the workflow to the next step (or completes it).
     *
     * - **all**: Every assignee must approve before the step advances.
     *   Individual approvals are tracked via WorkflowAction rows (not the
     *   step's approved_by column, which can only hold one user). When the
     *   last required approver acts, the step is marked approved and the
     *   workflow advances. Partial approvals leave the step pending and
     *   dispatch a "partially approved" event.
     */
    public function approve(Workflow $workflow, ?string $comments = null): void
    {
        if (!$workflow->isPending()) {
            throw new \RuntimeException("Workflow is not pending.");
        }

        $currentStep = $workflow->currentStep;
        if (!$currentStep || !$currentStep->isPending()) {
            throw new \RuntimeException("No pending step to approve.");
        }

        $user = Auth::user();
        $workspaceId = $this->resolveWorkspaceId($workflow);

        if (!$this->guard->canApprove($user, $currentStep->roles ?? [], $workspaceId)) {
            throw new AuthorizationException('You are not authorized to approve this workflow step.');
        }

        $mode = $currentStep->approval_mode ?? 'any';

        if ($mode === 'all') {
            $this->approveAllMode($workflow, $currentStep, $comments, $workspaceId);
            return;
        }

        // Default: any mode — single approval advances the step.
        $nextStep = DB::transaction(function () use ($workflow, $currentStep, $comments) {
            $currentStep->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'comments' => $comments,
            ]);

            $this->logAction($workflow, $currentStep, 'approved', $comments);

            return $this->advanceToNextStep($workflow);
        });

        event(new WorkflowApproved($workflow, $currentStep, Auth::id(), $nextStep === null));

        // Notify the next step's approvers, or every approver once the
        // workflow has fully completed.
        $recipientIds = $nextStep
            ? $this->resolveStepRecipientIds($nextStep, $workspaceId)
            : $this->resolveAllApproverIds($workflow, $workspaceId);

        $this->notifyTransition($workflow, 'approved', $recipientIds, [
            'step_name' => $currentStep->name,
            'comments' => $comments ?? '',
        ]);
    }

    /**
     * Handle approval when the step's resolution mode is "all".
     *
     * Every assignee must approve before the step advances. Individual
     * approvals are tracked as WorkflowAction rows. When the last required
     * approver acts, the step is marked approved and the workflow advances.
     */
    protected function approveAllMode(
        Workflow $workflow,
        WorkflowStep $currentStep,
        ?string $comments,
        ?string $workspaceId,
    ): void {
        $userId = Auth::id();

        // Guard: the same user cannot approve twice.
        $alreadyApproved = WorkflowAction::query()
            ->where('step_id', $currentStep->id)
            ->where('action', 'approved')
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyApproved) {
            throw new \RuntimeException('You have already approved this step.');
        }

        // Log this individual approval (does NOT mark the step approved yet).
        $this->logAction($workflow, $currentStep, 'approved', $comments);

        // Resolve all required approver user IDs.
        $requiredIds = $this->approvers->resolve(
            $currentStep->roles ?? [],
            $workspaceId,
        );

        // Count distinct users who have approved this step so far.
        $distinctApproverCount = WorkflowAction::query()
            ->where('step_id', $currentStep->id)
            ->where('action', 'approved')
            ->distinct('user_id')
            ->count('user_id');

        // If not all required approvers have acted, leave the step pending.
        if ($distinctApproverCount < count($requiredIds)) {
            $remaining = count($requiredIds) - $distinctApproverCount;

            event(new WorkflowApproved($workflow, $currentStep, $userId, false));

            $this->notifyTransition($workflow, 'approved', $requiredIds, [
                'step_name' => $currentStep->name,
                'comments' => $comments ?? '',
                'partial' => true,
                'remaining' => $remaining,
            ]);

            return;
        }

        // All required approvers have acted — mark the step approved and advance.
        $nextStep = DB::transaction(function () use ($workflow, $currentStep, $comments, $userId) {
            $currentStep->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'comments' => $comments,
            ]);

            return $this->advanceToNextStep($workflow);
        });

        event(new WorkflowApproved($workflow, $currentStep, $userId, $nextStep === null));

        $recipientIds = $nextStep
            ? $this->resolveStepRecipientIds($nextStep, $workspaceId)
            : $this->resolveAllApproverIds($workflow, $workspaceId);

        $this->notifyTransition($workflow, 'approved', $recipientIds, [
            'step_name' => $currentStep->name,
            'comments' => $comments ?? '',
        ]);
    }

    /**
     * Reject the current step and terminate the workflow.
     */
    public function reject(Workflow $workflow, ?string $comments = null): void
    {
        if (!$workflow->isPending()) {
            throw new \RuntimeException("Workflow is not pending.");
        }

        $currentStep = $workflow->currentStep;
        if (!$currentStep || !$currentStep->isPending()) {
            throw new \RuntimeException("No pending step to reject.");
        }

        $user = Auth::user();
        $workspaceId = $this->resolveWorkspaceId($workflow);

        if (!$this->guard->canApprove($user, $currentStep->roles ?? [], $workspaceId)) {
            throw new AuthorizationException('You are not authorized to reject this workflow step.');
        }

        DB::transaction(function () use ($workflow, $currentStep, $comments) {
            $currentStep->update([
                'status' => 'rejected',
                'comments' => $comments,
            ]);

            $workflow->update([
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            $this->logAction($workflow, $currentStep, 'rejected', $comments);
        });

        event(new WorkflowRejected($workflow, $currentStep, Auth::id(), $comments));

        $recipientIds = $workflow->submitted_by ? [$workflow->submitted_by] : [];

        $this->notifyTransition($workflow, 'rejected', $recipientIds, [
            'step_name' => $currentStep->name,
            'comments' => $comments ?? '',
        ]);
    }

    /**
     * Recall (cancel) a pending workflow.
     */
    public function recall(Workflow $workflow): void
    {
        if (!$workflow->isPending()) {
            throw new \RuntimeException("Only pending workflows can be recalled.");
        }

        $workspaceId = $this->resolveWorkspaceId($workflow);
        $notifiedStep = $workflow->currentStep;

        DB::transaction(function () use ($workflow) {
            $workflow->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            $this->logAction($workflow, null, 'recalled');
        });

        event(new WorkflowRecalled($workflow, Auth::id()));

        $this->notifyTransition(
            $workflow,
            'recalled',
            $this->resolveStepRecipientIds($notifiedStep, $workspaceId),
            [],
        );
    }

    /**
     * Resolve a workflow definition, DB-first with config fallback.
     *
     * Active rows in the workflow_definitions table take priority so that
     * definitions created through the workflow definition wizard are honoured
     * by the engine. When no active DB row exists, the legacy config-driven
     * definition is returned unchanged — preserving backward compatibility for
     * existing config-based workflows.
     *
     * Rows with `is_active = false` are skipped in the DB-first lookup, causing
     * a fallback to config (or an exception upstream when neither exists).
     * "Inactive" therefore means "un-startable", not merely "hidden".
     */
    public function getDefinition(string $key): ?array
    {
        $definition = WorkflowDefinition::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->with('steps')
            ->first();

        if ($definition) {
            return $this->hydrateFromModel($definition);
        }

        return config("ui-library.workflows.definitions.{$key}");
    }

    /**
     * Convert a persisted WorkflowDefinition (+ ordered steps) into the same
     * array shape the engine expects from config-driven definitions.
     *
     * @return array{label?: string, name?: string, entity_type?: string, initiators: array<int|string>, steps: array<int, array<string, mixed>>}
     */
    private function hydrateFromModel(WorkflowDefinition $definition): array
    {
        $steps = [];
        $initiators = [];

        foreach ($definition->steps as $step) {
            $assignees = $this->resolveAssigneeIdentifiers($step);

            // Bug 2: Respect tier_type — initiator steps are collected
            // separately for submit-time authorization and are NOT added
            // to the approval steps array.
            if ($step->tier_type === 'initiator') {
                $initiators = $assignees;
                continue;
            }

            // Authorizers are always parallel: ANY ONE authorizer can give
            // final approval. A manually-authored or DB-edited authorizer row
            // may carry a stray `resolution_mode = 'all'`, which would make
            // every authorizer approve — so force `any` here regardless of the
            // stored value. Reviewers keep their stored `any`/`all` mode.
            $approvalMode = $step->tier_type === 'authorizer'
                ? 'any'
                : ($step->resolution_mode ?? 'any');

            // review and authorizer steps become runtime approval steps.
            $steps[] = [
                'name' => $step->name,
                'step_type' => 'approval',
                'approval_mode' => $approvalMode,
                'roles' => $assignees,
            ];
        }

        return [
            'label' => $definition->name,
            'name' => $definition->name,
            'entity_type' => $definition->entity_type,
            'is_active' => $definition->is_active,
            'initiators' => $initiators,
            'steps' => $steps,
        ];
    }

    /**
     * Extract the assignee identifiers the engine should resolve at runtime.
     *
     * The definition stores `{ "mode": "users|roles|mixed", "ids": [...] }`.
     * The engine's runtime WorkflowStep carries a flat `roles` list which is
     * passed to the configured ApproverResolver for expansion, so the stored
     * ids are emitted as-is. The ids array is self-describing: integers are
     * user IDs and strings are role names (see the ApproverResolver contract).
     */
    private function resolveAssigneeIdentifiers(WorkflowDefinitionStep $step): array
    {
        $assignees = $step->assignees;

        if (!is_array($assignees)) {
            return [];
        }

        $ids = $assignees['ids'] ?? [];

        return is_array($ids) ? array_values(array_filter($ids)) : [];
    }

    /**
     * Check if an entity already has an active workflow.
     */
    public function hasActiveWorkflow(Workflowable $entity): bool
    {
        return Workflow::where('workflowable_type', get_class($entity))
            ->where('workflowable_id', $entity->getWorkflowableId())
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Advance to the next pending step, or complete the workflow.
     *
     * @return WorkflowStep|null The newly current step, or null when the
     *                           workflow has completed.
     */
    protected function advanceToNextStep(Workflow $workflow): ?WorkflowStep
    {
        $nextStep = $workflow->steps()
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();

        if ($nextStep) {
            $workflow->current_step = $nextStep->id;
            $workflow->save();

            return $nextStep;
        }

        $workflow->update([
            'status' => 'approved',
            'completed_at' => now(),
        ]);

        $this->logAction($workflow, null, 'completed');

        return null;
    }

    /**
     * Log a workflow action.
     */
    protected function logAction(Workflow $workflow, ?WorkflowStep $step, string $action, ?string $comments = null): void
    {
        WorkflowAction::create([
            'workflow_id' => $workflow->id,
            'step_id' => $step?->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'comments' => $comments,
        ]);
    }

    /**
     * Resolve the workspace identifier from the workflow context.
     */
    protected function resolveWorkspaceId(Workflow $workflow): ?string
    {
        $context = $workflow->context;

        if (!is_array($context)) {
            return null;
        }

        $workspaceId = $context['workspace_id'] ?? null;

        return $workspaceId !== null ? (string) $workspaceId : null;
    }

    /**
     * Resolve the user IDs that should be notified for a step.
     */
    protected function resolveStepRecipientIds(?WorkflowStep $step, ?string $workspaceId = null): array
    {
        if (!$step) {
            return [];
        }

        $roles = $step->roles ?? [];

        if (is_array($roles) && $roles !== []) {
            return $this->approvers->resolve($roles, $workspaceId);
        }

        return $step->assigned_to ? [$step->assigned_to] : [];
    }

    /**
     * Resolve every approver across all steps of the workflow.
     */
    protected function resolveAllApproverIds(Workflow $workflow, ?string $workspaceId = null): array
    {
        $ids = [];

        foreach ($workflow->steps as $step) {
            $ids = array_merge($ids, $this->resolveStepRecipientIds($step, $workspaceId));
        }

        return array_values(array_unique($ids));
    }

    /**
     * Dispatch a workflow notification, but only when the workflow definition
     * has a populated "notifications" section.
     */
    protected function notifyTransition(Workflow $workflow, string $event, array $recipientIds, array $data = []): void
    {
        $config = $this->notificationConfig($workflow->definition_key);

        if ($config === [] || (array_key_exists('enabled', $config) && !$config['enabled'])) {
            return;
        }

        $types = $config['types'] ?? [];

        $type = array_key_exists($event, $types)
            ? $types[$event]
            : "workflow_{$event}";

        // A null/empty type means the event's notification toggle is off. Skip it.
        if ($type === null || $type === '') {
            return;
        }

        $type = (string) $type;
        $recipientIds = array_values(array_unique(array_filter($recipientIds)));

        $useAsync = (bool) config('ui-library.notifications.queue', false);

        foreach ($recipientIds as $recipientId) {
            $notifiable = $this->resolveNotifiable($recipientId);

            if ($notifiable instanceof Notifiable) {
                $payload = array_merge([
                    'workflow_id' => $workflow->id,
                    'workflow_key' => $workflow->definition_key,
                    'workflow_status' => $workflow->status,
                ], $data);

                if ($useAsync) {
                    $this->notifications->dispatchAsync($notifiable, $type, $payload);
                } else {
                    $this->notifications->dispatch($notifiable, $type, $payload);
                }
            }
        }
    }

    /**
     * Read the optional notification configuration for a workflow definition.
     *
     * DB-first with config fallback: wizard-created definitions store their
     * notification settings in the `workflow_definitions.notifications` JSON
     * column. When a DB row has a populated `notifications` value it is used
     * directly; otherwise the legacy config-driven definition is consulted.
     */
    protected function notificationConfig(string $definitionKey): array
    {
        $definition = WorkflowDefinition::query()
            ->where('key', $definitionKey)
            ->where('is_active', true)
            ->first();

        $dbNotifications = $definition?->notifications;

        if (is_array($dbNotifications) && $dbNotifications !== []) {
            return $dbNotifications;
        }

        $config = config("ui-library.workflows.definitions.{$definitionKey}.notifications", []);

        return is_array($config) ? $config : [];
    }

    /**
     * Resolve a notifiable entity from a user ID.
     */
    protected function resolveNotifiable(int|string $id): ?Notifiable
    {
        $userModel = config('ui-library.user.model', \App\Models\User::class);
        $user = $userModel::find($id);

        return $user instanceof Notifiable ? $user : null;
    }
}
