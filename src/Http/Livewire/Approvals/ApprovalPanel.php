<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Approvals;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverLabelResolver;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Models\WorkflowAction;
use QuickerFaster\UILibrary\Services\Approvals\ApprovalGuard;
use QuickerFaster\UILibrary\Services\Workflow\WorkflowEngine;

/**
 * Combined approval panel that renders both the approve/reject/recall actions
 * and the workflow history timeline in a single cohesive component.
 *
 * This is a convenience wrapper around ApprovalActions + ApprovalHistoryTimeline.
 * The individual components remain available for standalone use.
 */
class ApprovalPanel extends Component
{
    public ?int $workflowId = null;

    public string $displayMode = 'banner';

    public bool $showCommentModal = false;

    public string $actionType = '';

    public string $comments = '';

    public bool $showFullHistory = false;

    protected $listeners = [
        'refreshApprovalActions' => '$refresh',
        'refreshApprovalTimeline' => '$refresh',
    ];

    protected WorkflowEngine $engine;

    protected ApprovalGuard $guard;

    protected ApproverResolver $approvers;

    protected ApproverLabelResolver $labels;

    public function boot(): void
    {
        $this->engine = app(WorkflowEngine::class);
        $this->guard = app(ApprovalGuard::class);
        $this->approvers = app(ApproverResolver::class);
        $this->labels = app(ApproverLabelResolver::class);
    }

    public function mount(?Workflow $workflow = null, ?int $workflowId = null, string $displayMode = 'banner'): void
    {
        $this->workflowId = $workflowId ?? ($workflow?->getKey() ?? null);
        $this->displayMode = $displayMode;
    }

    // -----------------------------------------------------------------------
    // Approval actions (delegated to WorkflowEngine)
    // -----------------------------------------------------------------------

    public function openCommentModal(string $action): void
    {
        $this->actionType = $action;
        $this->comments = '';
        $this->showCommentModal = true;
    }

    public function confirmAction(): void
    {
        if ($this->actionType === 'approve') {
            $this->approve($this->comments);
        } elseif ($this->actionType === 'reject') {
            $this->reject($this->comments);
        } elseif ($this->actionType === 'recall') {
            $this->recall();
        }
    }

    public function approve(?string $comments = null): void
    {
        $this->runTransition(
            fn (Workflow $workflow) => $this->engine->approve($workflow, $this->normalizeComments($comments)),
            'Workflow approved.'
        );
    }

    public function reject(?string $comments = null): void
    {
        $this->runTransition(
            fn (Workflow $workflow) => $this->engine->reject($workflow, $this->normalizeComments($comments)),
            'Workflow rejected.'
        );
    }

    public function recall(): void
    {
        $this->runTransition(
            fn (Workflow $workflow) => $this->engine->recall($workflow),
            'Workflow recalled.'
        );
    }

    public function toggleFullHistory(): void
    {
        $this->showFullHistory = ! $this->showFullHistory;
    }

    // -----------------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------------

    public function render()
    {
        $workflow = $this->resolveWorkflow();
        $permissions = $this->permissions($workflow);

        $actions = $workflow
            ? WorkflowAction::query()
                ->with('step')
                ->where('workflow_id', $workflow->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->map(fn (WorkflowAction $action) => $this->formatAction($action))
            : collect();

        return view('qf::livewire.approvals.approval-panel', array_merge([
            'workflow' => $workflow,
            'approvers' => $workflow ? $this->resolveApprovers($workflow) : [],
            'actions' => $actions,
        ], $permissions));
    }

    // -----------------------------------------------------------------------
    // Transition helpers
    // -----------------------------------------------------------------------

    protected function runTransition(callable $transition, string $message): void
    {
        $workflow = $this->resolveWorkflow();

        if (! $workflow) {
            $this->notify('Workflow not found.', 'error');

            return;
        }

        try {
            $transition($workflow);

            $this->resetModal();
            $this->dispatch('refreshApprovalActions');
            $this->dispatch('refreshApprovalTimeline');
            $this->dispatch('refreshApprovalRequests');
            $this->dispatch('refreshDetail');
            $this->notify($message, 'success');
        } catch (AuthorizationException $e) {
            $this->notify($e->getMessage(), 'error');
        } catch (\RuntimeException $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    // -----------------------------------------------------------------------
    // Workflow resolution
    // -----------------------------------------------------------------------

    protected function resolveWorkflow(): ?Workflow
    {
        if (! $this->workflowId) {
            return null;
        }

        return Workflow::query()
            ->with(['currentStep', 'steps'])
            ->find($this->workflowId);
    }

    // -----------------------------------------------------------------------
    // Permissions
    // -----------------------------------------------------------------------

    protected function permissions(?Workflow $workflow): array
    {
        $canApprove = false;
        $canReject = false;
        $canRecall = false;

        if ($workflow && $workflow->isPending()) {
            $user = Auth::user();

            if ($user) {
                $currentStep = $workflow->currentStep;

                if ($currentStep && $currentStep->isPending()) {
                    $canAct = $this->guard->canApprove(
                        $user,
                        $currentStep->roles ?? [],
                        $this->resolveWorkspaceId($workflow)
                    );

                    $canApprove = $canAct;
                    $canReject = $canAct;
                }

                $canRecall = (string) $workflow->submitted_by === (string) $user->getAuthIdentifier();
            }
        }

        return compact('canApprove', 'canReject', 'canRecall');
    }

    // -----------------------------------------------------------------------
    // Approver resolution
    // -----------------------------------------------------------------------

    protected function resolveApprovers(Workflow $workflow): array
    {
        $step = $workflow->currentStep;

        if (! $step) {
            return [];
        }

        $workspaceId = $this->resolveWorkspaceId($workflow);

        if (is_array($step->roles) && $step->roles !== []) {
            $userIds = $this->approvers->resolve($step->roles, $workspaceId);
        } elseif ($step->assigned_to) {
            $userIds = [$step->assigned_to];
        } else {
            return [];
        }

        return array_map(function ($userId) {
            return [
                'id' => $userId,
                'label' => $this->labels->label($userId),
                'avatar' => $this->labels->avatar($userId),
                'profileRoute' => $this->labels->profileRoute($userId),
            ];
        }, array_values(array_unique($userIds)));
    }

    protected function resolveWorkspaceId(Workflow $workflow): ?string
    {
        $context = $workflow->context;

        if (! is_array($context)) {
            return null;
        }

        $workspaceId = $context['workspace_id'] ?? null;

        return $workspaceId !== null ? (string) $workspaceId : null;
    }

    // -----------------------------------------------------------------------
    // Timeline formatting (from ApprovalHistoryTimeline)
    // -----------------------------------------------------------------------

    protected function formatAction(WorkflowAction $action): array
    {
        return [
            'id' => $action->id,
            'action' => $action->action,
            'label' => $this->actionLabel($action->action),
            'status' => $this->actionStatus($action->action),
            'step_name' => $action->step?->name,
            'actor' => $action->user_id ? $this->labels->label($action->user_id) : null,
            'actor_avatar' => $action->user_id ? $this->labels->avatar($action->user_id) : null,
            'actor_profile_route' => $action->user_id ? $this->labels->profileRoute($action->user_id) : null,
            'comments' => $action->comments,
            'created_at' => $action->created_at,
        ];
    }

    protected function actionLabel(string $action): string
    {
        return match ($action) {
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'recalled' => 'Recalled',
            default => ucfirst($action),
        };
    }

    protected function actionStatus(string $action): string
    {
        return match ($action) {
            'submitted' => 'pending',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'completed' => 'approved',
            'recalled' => 'cancelled',
            default => 'pending',
        };
    }

    // -----------------------------------------------------------------------
    // Utilities
    // -----------------------------------------------------------------------

    protected function normalizeComments(?string $comments): ?string
    {
        $comments = $comments ?? $this->comments;

        return $comments !== null && trim($comments) !== '' ? $comments : null;
    }

    protected function resetModal(): void
    {
        $this->showCommentModal = false;
        $this->actionType = '';
        $this->comments = '';
    }

    protected function notify(string $message, string $type): void
    {
        $this->dispatch('showAlert', [
            'type' => $type,
            'message' => $message,
            'autoClose' => true,
        ]);
    }
}