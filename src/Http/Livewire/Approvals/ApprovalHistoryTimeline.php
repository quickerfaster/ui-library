<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Approvals;

use Livewire\Component;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverLabelResolver;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Models\WorkflowAction;

/**
 * Renders the chronological action history for a Workflow.
 *
 * Each WorkflowAction record is resolved into a display-ready entry showing
 * who acted (via ApproverLabelResolver), the action label, the affected step,
 * the timestamp, and any comments.
 */
class ApprovalHistoryTimeline extends Component
{
    public ?int $workflowId = null;

    public string $displayMode = 'full';

    protected $listeners = ['refreshApprovalTimeline' => '$refresh'];

    protected ApproverLabelResolver $labels;

    public function boot(): void
    {
        $this->labels = app(ApproverLabelResolver::class);
    }

    public function mount(?Workflow $workflow = null, ?int $workflowId = null, string $displayMode = 'full'): void
    {
        $this->workflowId = $workflowId ?? ($workflow?->getKey() ?? null);
        $this->displayMode = $displayMode;
    }

    public function render()
    {
        $workflow = $this->resolveWorkflow();

        $actions = $workflow
            ? WorkflowAction::query()
                ->with('step')
                ->where('workflow_id', $workflow->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->map(fn (WorkflowAction $action) => $this->formatAction($action))
            : collect();

        return view('qf::livewire.approvals.timeline', [
            'workflow' => $workflow,
            'actions' => $actions,
        ]);
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
}
