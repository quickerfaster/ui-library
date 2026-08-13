<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApprovalTier model for the UI Library.
 *
 * Represents a single tier/step in an approval workflow.
 */
class ApprovalTier extends Model
{
    protected $table = 'approval_tiers';

    protected $fillable = [
        'approval_request_id',
        'sequence',
        'approver_id',
        'status',
        'comments',
        'decided_at',
    ];

    /**
     * Get the approval request this tier belongs to.
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }
}