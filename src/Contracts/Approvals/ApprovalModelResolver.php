<?php

namespace QuickerFaster\UILibrary\Contracts\Approvals;

interface ApprovalModelResolver
{
    /**
     * Get the FQCN for the ApprovalRequest model.
     */
    public function resolveRequestModel(): string;

    /**
     * Get the FQCN for the ApprovalTier model.
     */
    public function resolveTierModel(): string;

    /**
     * Get the FQCN for the ApprovalLog model.
     */
    public function resolveLogModel(): string;

    /**
     * Get the FQCN for the ApprovalTierApproval model.
     */
    public function resolveTierApprovalModel(): string;
}
