<?php

namespace QuickerFaster\UILibrary\Services\Approvals;

use QuickerFaster\UILibrary\Contracts\Approvals\ApprovalModelResolver as ResolverContract;

class ApprovalModelResolver implements ResolverContract
{
    public function resolveRequestModel(): string
    {
        return config('ui-library.approvals.models.request', \QuickerFaster\UILibrary\Models\ApprovalRequest::class);
    }

    public function resolveTierModel(): string
    {
        return config('ui-library.approvals.models.tier', \QuickerFaster\UILibrary\Models\ApprovalTier::class);
    }

    public function resolveLogModel(): string
    {
        return config('ui-library.approvals.models.log', \QuickerFaster\UILibrary\Models\ApprovalLog::class);
    }

    public function resolveTierApprovalModel(): string
    {
        return config('ui-library.approvals.models.tier_approval', \QuickerFaster\UILibrary\Models\ApprovalTierApproval::class);
    }
}
