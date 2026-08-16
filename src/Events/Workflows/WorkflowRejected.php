<?php

namespace QuickerFaster\UILibrary\Events\Workflows;

use Illuminate\Foundation\Events\Dispatchable;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Models\WorkflowStep;

/**
 * Fired when a workflow step is rejected.
 */
class WorkflowRejected
{
    use Dispatchable;

    public function __construct(
        public readonly Workflow $workflow,
        public readonly WorkflowStep $step,
        public readonly int|string|null $rejectingUserId = null,
        public readonly ?string $reason = null,
    ) {}
}
