<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Approvals;

use Livewire\Component;
use App\Modules\System\Models\ApprovalRequest;
use QuickerFaster\UILibrary\Services\Config\Approvals\ApprovalConfigResolver;
use QuickerFaster\UILibrary\Services\Approvals\ApprovalEngine;

class ApprovalActions extends Component
{
    public string $configKey;
    public int $approvableId;
    public ?ApprovalRequest $request = null;
    public bool $showCommentModal = false;
    public string $actionType = ''; // 'approve' or 'reject'
    public string $comments = '';
    public bool $canSubmit = false;
    public bool $canApprove = false;
    public bool $canReject = false;
    public bool $canRecall = false;

    protected $listeners = ['refreshApprovalActions' => '$refresh'];

    public function mount(string $configKey, int $approvableId): void
    {
        $this->configKey = $configKey;
        $this->approvableId = $approvableId;
        $this->loadApprovalRequest();
    }

    protected function loadApprovalRequest(): void
    {
        $resolver = app(ApprovalConfigResolver::class, ['configKey' => $this->configKey]);
        $modelClass = $resolver->getModelClass();
        $approvable = $modelClass::find($this->approvableId);

        if ($approvable && $approvable->approvalRequest) {
            $this->request = $approvable->approvalRequest;
            $this->determinePermissions();
        } else {
            $this->canSubmit = true;
        }
    }

    protected function determinePermissions(): void
    {
        if (!$this->request) {
            return;
        }

        $user = auth()->user();
        if (!$user) return; // Abort when user is not logged-in

        $currentTier = $this->request->currentTier;

        // Submit is only for draft (not yet submitted) – we don't have draft in our schema, but can be used.
        $this->canSubmit = false; // already submitted if request exists.

        if ($this->request->status === 'pending' && $currentTier) {
            // TODO: replace with actual role check against $currentTier->roles
            $hasRole = true; // placeholder: check if user has any role in $currentTier->roles
            $this->canApprove = $hasRole;
            $this->canReject = $hasRole;
        }


        /*if ($this->request->status === 'pending' && $currentTier) {
            $requiredRoles = $currentTier->roles; // e.g., ['payroll_officer']
            $hasRole = $user->hasAnyRole($requiredRoles);
            $this->canApprove = $hasRole;
            $this->canReject = $hasRole;
        }*/



        // Recall allowed only for the submitter when status is pending
        $this->canRecall = ($this->request->status === 'pending' && $this->request->submitted_by === $user->id);
    }

    public function submitForApproval(): void
    {
        $resolver = app(ApprovalConfigResolver::class, ['configKey' => $this->configKey]);
        $modelClass = $resolver->getModelClass();
        $approvable = $modelClass::find($this->approvableId);
        $engine = app(ApprovalEngine::class, ['configResolver' => $resolver]);
        $engine->startApproval($approvable, auth()->user());

        $this->dispatch('refreshApprovalActions');
        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => 'Approval request submitted successfully.',
            'autoClose' => true,
        ]);
    }

    public function openCommentModal(string $action): void
    {
        $this->actionType = $action;
        $this->comments = '';
        $this->showCommentModal = true;
    }

    public function confirmAction(): void
    {
        $resolver = app(ApprovalConfigResolver::class, ['configKey' => $this->configKey]);
        $engine = app(ApprovalEngine::class, ['configResolver' => $resolver]);

        if ($this->actionType === 'approve') {
            $engine->approve($this->request, auth()->user(), $this->comments);
            $message = 'Request approved.';
        } elseif ($this->actionType === 'reject') {
            $engine->reject($this->request, auth()->user(), $this->comments);
            $message = 'Request rejected.';
        }

        $this->showCommentModal = false;
        $this->dispatch('refreshApprovalActions');
        $this->dispatch('refreshApprovalTimeline');
        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => $message,
            'autoClose' => true,
        ]);
    }

    public function recall(): void
    {
        $resolver = app(ApprovalConfigResolver::class, ['configKey' => $this->configKey]);
        $engine = app(ApprovalEngine::class, ['configResolver' => $resolver]);
        $engine->recall($this->request, auth()->user());

        $this->dispatch('refreshApprovalActions');
        $this->dispatch('refreshApprovalTimeline');
        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => 'Approval request cancelled.',
            'autoClose' => true,
        ]);
    }

    public function render()
    {
        return view('qf::livewire.approvals.actions');
    }
}