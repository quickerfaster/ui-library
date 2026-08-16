<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Approvals;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverLabelResolver;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Services\Approvals\ApprovalGuard;
use QuickerFaster\UILibrary\Services\Workflow\WorkflowEngine;

/**
 * Renders the approve / reject / recall actions for a Workflow.
 *
 * The component delegates every state transition to the WorkflowEngine and
 * uses ApprovalGuard to decide which buttons the current user may perform.
 * Approver display labels and avatars are resolved through the
 * ApproverLabelResolver contract.
 */
class ApprovalActions extends Component
{
    public ?int $workflowId = null;

    public bool $showCommentModal = false;

    public string $actionType = '';

    public string $comments = '';

    protected $listeners = ['refreshApprovalActions' => '$refresh'];

    public function __construct(
        protected WorkflowEngine $engine,
        protected ApprovalGuard $guard,
        protected ApproverResolver $approvers,
        protected ApproverLabelResolver $labels,
    ) {
    }

    public function mount(?Workflow $workflow = null, ?int $workflowId = null): void
    {
        $this->workflowId = $workflowId ?? ($workflow?->getKey() ?? null);
    }

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

    public function render()
    {
        $workflow = $this->resolveWorkflow();
        $permissions = $this->permissions($workflow);

        return view('qf::livewire.approvals.actions', array_merge([
            'workflow' => $workflow,
            'approvers' => $workflow ? $this->resolveApprovers($workflow) : [],
        ], $permissions));
    }

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
            $this->notify($message, 'success');
        } catch (AuthorizationException $e) {
            $this->notify($e->getMessage(), 'error');
        } catch (\RuntimeException $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    protected function resolveWorkflow(): ?Workflow
    {
        if (! $this->workflowId) {
            return null;
        }

        return Workflow::query()
            ->with(['currentStep', 'steps'])
            ->find($this->workflowId);
    }

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

    /**
     * Resolve the approvers for the workflow's current step as label/avatar
     * payloads for the UI.
     */
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
