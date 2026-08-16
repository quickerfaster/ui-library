<?php

namespace QuickerFaster\UILibrary\Events\Workflows;

use Illuminate\Foundation\Events\Dispatchable;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Models\WorkflowStep;

/**
 * Fired when a workflow step is approved.
 *
 * The $completed flag is true when this approval was the final step and the
 * entire workflow transitioned to "approved".
 */
class WorkflowApproved
{
    use Dispatchable;

    public function __construct(
        public readonly Workflow $workflow,
        public readonly ?WorkflowStep $step = null,
        public readonly int|string|null $approvingUserId = null,
        public readonly bool $completed = false,
    ) {}
}
