<?php

namespace QuickerFaster\UILibrary\Traits\Approvals;

use QuickerFaster\UILibrary\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasApproval
{
    /**
     * Get the approval request associated with this model.
     */
    public function approvalRequest(): MorphOne
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable');
    }

    /**
     * Check if the model currently has a pending approval request.
     */
    public function isUnderApproval(): bool
    {
        $approval = $this->approvalRequest;

        return $approval && in_array($approval->status, ['draft', 'pending']);
    }

    /**
     * Get the active (pending) approval request, or null if none.
     */
    public function getActiveApproval(): ?ApprovalRequest
    {
        $approval = $this->approvalRequest;

        return ($approval && in_array($approval->status, ['draft', 'pending'])) ? $approval : null;
    }

    /**
     * Check if the model can be edited based on approval lock configuration.
     * This method does NOT automatically enforce anything; it only returns a boolean.
     * The developer must call it manually when needed.
     *
     * @param string $configKey The approval config key (e.g., 'module.approvals.approval_name')
     * @return bool
     */
    public function canBeEditedWhileUnderApproval(string $configKey): bool
    {
        $resolver = app(\QuickerFaster\UILibrary\Services\Config\Approvals\ApprovalConfigResolver::class, ['configKey' => $configKey]);
        $lock = $resolver->lockWhileApproving();

        if (!$lock) {
            return true; // editing always allowed
        }

        // If lock is enabled, editing is only allowed when there is no active approval
        return !$this->isUnderApproval();
    }
}