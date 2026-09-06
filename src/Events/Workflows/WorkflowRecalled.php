<?php

namespace QuickerFaster\UILibrary\Events\Workflows;

use Illuminate\Foundation\Events\Dispatchable;
use QuickerFaster\UILibrary\Models\Workflow;

/**
 * Fired when a submitted workflow is recalled (cancelled).
 */
class WorkflowRecalled
{
    use Dispatchable;

    public function __construct(
        public readonly Workflow $workflow,
        public readonly int|string|null $recallingUserId = null,
    ) {}
}
