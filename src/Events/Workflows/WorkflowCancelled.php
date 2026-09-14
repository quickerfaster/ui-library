<?php

namespace QuickerFaster\UILibrary\Events\Workflows;

use Illuminate\Foundation\Events\Dispatchable;
use QuickerFaster\UILibrary\Models\Workflow;

class WorkflowCancelled
{
    use Dispatchable;

    public function __construct(
        public Workflow $workflow,
        public ?string $comments = null,
    ) {}
}