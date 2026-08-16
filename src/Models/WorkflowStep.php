<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A runtime workflow step (one per submitted workflow instance).
 *
 * The `approval_mode` column is the **runtime** equivalent of the definition
 * model's `resolution_mode` column:
 *
 * - `any` — a single approval advances the step (parallel assignees).
 * - `all` — every resolved assignee must approve before the step advances.
 *
 * [`WorkflowEngine::hydrateFromModel()`](../Services/Workflow/WorkflowEngine.php)
 * copies `WorkflowDefinitionStep::resolution_mode` into this column. Authorizer
 * tiers are always forced to `any` (authorizers are parallel — ANY ONE can
 * give final approval), while review tiers keep their stored `any`/`all` mode.
 */
class WorkflowStep extends Model
{
    protected $fillable = [
        'workflow_id',
        'name',
        'sequence',
        'step_type',
        'approval_mode',
        'roles',
        'assigned_to',
        'status',
        'approved_by',
        'approved_at',
        'comments',
    ];

    protected $casts = [
        'roles' => 'array',
        'approved_at' => 'datetime',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
