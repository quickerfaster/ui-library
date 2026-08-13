<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ApprovalRequest model for the UI Library.
 *
 * This replaces the App\Modules\System\Models\ApprovalRequest reference
 * that was previously coupled to the QuickHR application.
 *
 * Consuming applications should either:
 * 1. Use this model directly, or
 * 2. Extend/replace it via Laravel's model binding
 */
class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'status',
        'submitted_by',
        'submitted_at',
    ];

    /**
     * Get the parent approvable model.
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the approval tiers for this request.
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(ApprovalTier::class, 'approval_request_id')->orderBy('sequence');
    }
}