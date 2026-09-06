<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A workflow definition template (persisted in `workflow_definitions`).
 *
 * ## `key` vs `entity_type`
 *
 * - `key` is the sole lookup key used to match an entity to a definition. It is
 *   what [`Workflowable::getWorkflowDefinitionKey()`](../Contracts/Workflow/Workflowable.php)
 *   returns and what [`WorkflowEngine::getDefinition()`](../Services/Workflow/WorkflowEngine.php)
 *   resolves against (DB-first, config fallback).
 * - `entity_type` is a descriptive label only (e.g., "Purchase Order"). It is
 *   NOT used to match entities to definitions at runtime.
 *
 * ## `is_active` semantics
 *
 * `is_active = false` means the definition is un-startable: it is skipped in
 * the DB-first lookup in [`WorkflowEngine::getDefinition()`](../Services/Workflow/WorkflowEngine.php),
 * causing a fallback to config (or an exception upstream when neither exists).
 * "Inactive" is not merely "hidden" — it prevents new workflows from starting.
 */
class WorkflowDefinition extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'entity_type',
        'is_active',
        'notifications',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'notifications' => 'array',
    ];

    public function steps()
    {
        return $this->hasMany(WorkflowDefinitionStep::class)
            ->orderBy('sequence');
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}