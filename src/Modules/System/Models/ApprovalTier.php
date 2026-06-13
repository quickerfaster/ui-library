<?php

namespace App\Modules\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalTier extends Model
{
    protected $table = 'approval_tiers';

    protected $fillable = [
        'approval_request_id', 'tier_type', 'sequence', 'name', 'roles',
        'approval_mode', 'status', 'approved_by', 'approved_at', 'comments',
    ];

    protected $casts = [
        'roles' => 'array',
        'approved_at' => 'datetime',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function tierApprovals(): HasMany
    {
        return $this->hasMany(ApprovalTierApproval::class);
    }
}