<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single tier/step within a workflow definition template.
 *
 * ## Two distinct "mode" concepts
 *
 * This model carries two independent mode columns that are easily confused:
 *
 * | Column            | Purpose                                              | Values          |
 * |-------------------|------------------------------------------------------|-----------------|
 * | `resolution_mode` | How many assignees must act before the step advances | `any` or `all`  |
 * | `assignees.mode`  | Describes the mix of assignee types (informational)  | `users`, `roles`, `mixed` |
 *
 * `resolution_mode` is the **runtime enforcement** mode — it controls whether
 * the WorkflowEngine requires one (`any`) or every (`all`) assignee to approve
 * before advancing to the next step.
 *
 * `resolution_mode` is the **definition** column (`any`/`all`). When the
 * engine hydrates a definition into runtime steps, this value is copied to
 * [`WorkflowStep::approval_mode`](WorkflowStep.php) — the runtime equivalent.
 * Authorizer tiers are the one exception: they are always forced to `any`
 * regardless of their stored value (see
 * [`WorkflowEngine::hydrateFromModel()`](../Services/Workflow/WorkflowEngine.php)),
 * because authorizers are always parallel — ANY ONE can give final approval.
 *
 * `assignees.mode` is a **derived/informational** field computed from the
 * `assignees.ids` array. It describes whether the assignee list contains only
 * user IDs (`users`), only role names (`roles`), or both (`mixed`). It has no
 * effect on runtime behavior — the engine resolves all entries uniformly via
 * the ApproverResolver contract.
 */
class WorkflowDefinitionStep extends Model
{
    protected $fillable = [
        'workflow_definition_id',
        'sequence',
        'tier_type',
        'name',
        'description',
        'resolution_mode',
        'assignees',
    ];

    protected $casts = [
        'assignees' => 'array',
        'sequence' => 'integer',
    ];

    public function definition()
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function isInitiator(): bool
    {
        return $this->tier_type === 'initiator';
    }

    public function isReview(): bool
    {
        return $this->tier_type === 'review';
    }

    public function isAuthorizer(): bool
    {
        return $this->tier_type === 'authorizer';
    }
}