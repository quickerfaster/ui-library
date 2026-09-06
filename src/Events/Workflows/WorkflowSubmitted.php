<?php

namespace QuickerFaster\UILibrary\Events\Workflows;

use Illuminate\Foundation\Events\Dispatchable;
use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;
use QuickerFaster\UILibrary\Models\Workflow;

/**
 * Fired when a workflow is started/submitted.
 */
class WorkflowSubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly Workflow $workflow,
        public readonly ?Workflowable $workflowable = null,
    ) {}
}
