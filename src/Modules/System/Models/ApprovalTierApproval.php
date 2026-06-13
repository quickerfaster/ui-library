<?php

namespace App\Modules\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalTierApproval extends Model
{
    protected $table = 'approval_tier_approvals';

    protected $fillable = ['tier_id', 'user_id', 'comments', 'approved_at'];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(ApprovalTier::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}