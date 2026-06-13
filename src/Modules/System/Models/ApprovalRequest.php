<?php

namespace App\Modules\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $fillable = [
        'approvable_type', 'approvable_id', 'status', 'current_tier_id',
        'submitted_by', 'submitted_at', 'completed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(ApprovalTier::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class);
    }

    public function currentTier()
    {
        return $this->belongsTo(ApprovalTier::class, 'current_tier_id');
    }
}