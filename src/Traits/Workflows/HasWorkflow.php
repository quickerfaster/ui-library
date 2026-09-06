<?php

namespace QuickerFaster\UILibrary\Traits\Workflows;

use QuickerFaster\UILibrary\Models\Workflow;

/**
 * Convenience trait for Eloquent models that participate in the workflow
 * system by implementing {@see \QuickerFaster\UILibrary\Contracts\Workflow\Workflowable}.
 *
 * This trait provides the common workflow relationship plumbing so models
 * only need to supply the domain-specific pieces:
 *
 * ```php
 * use HasWorkflow;
 * ```
 *
 * The consuming model MUST still implement:
 * - {@see \QuickerFaster\UILibrary\Contracts\Workflow\Workflowable::getWorkflowDefinitionKey()}
 * - {@see \QuickerFaster\UILibrary\Contracts\Workflow\Workflowable::getWorkflowContext()}
 *
 * These are intentionally left to the model because they are domain-specific.
 * {@see HasWorkflow::getWorkflowableId()} is given a sensible default
 * (`$this->getKey()`) and may be overridden when the workflow identifier
 * differs from the primary key.
 */
trait HasWorkflow
{
    /**
     * The model's current workflow (the single most-recent workflow record).
     *
     * Returned unfiltered so callers can apply their own status/step filters.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function workflow()
    {
        return $this->morphOne(Workflow::class, 'workflowable')->latest();
    }

    /**
     * Full workflow history for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function workflows()
    {
        return $this->morphMany(Workflow::class, 'workflowable');
    }

    /**
     * The pending/latest active workflow, if any.
     *
     * Filters to status 'pending' and orders by latest first.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function activeWorkflow()
    {
        return $this->morphOne(Workflow::class, 'workflowable')
            ->where('status', 'pending')
            ->latest();
    }

    /**
     * Whether this model currently has an active pending workflow.
     */
    public function isUnderApproval(): bool
    {
        return $this->activeWorkflow()->exists();
    }

    /**
     * Default workflowable identifier using the model's primary key.
     *
     * Override if the workflow identifier differs from the model key.
     */
    public function getWorkflowableId(): int|string
    {
        return $this->getKey();
    }

    /**
     * Sync the model's status field with the given workflow's status.
     *
     * Called from event listeners after workflow transitions (approved, rejected, recalled).
     * The optional $statusMap allows consuming apps to map workflow statuses
     * (pending/approved/rejected/cancelled) to model-specific status values.
     *
     * @param  Workflow  $workflow  The workflow whose status should be synced
     * @param  array<string, string>|null  $statusMap  Optional mapping of workflow status → model status
     * @return $this
     */
    public function syncStatusFromWorkflow(Workflow $workflow, ?array $statusMap = null): static
    {
        if ($statusMap !== null && isset($statusMap[$workflow->status])) {
            $this->status = $statusMap[$workflow->status];
        } else {
            $this->status = $workflow->status;
        }

        $this->save();

        return $this;
    }
}