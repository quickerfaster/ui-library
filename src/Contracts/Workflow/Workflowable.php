<?php

namespace QuickerFaster\UILibrary\Contracts\Workflow;

interface Workflowable
{
    /**
     * Get the unique identifier for this workflowable entity.
     */
    public function getWorkflowableId(): int|string;

    /**
     * Get the workflow definition key (e.g., 'leave_request', 'expense_claim').
     * This maps to a key in config('ui-library.workflows.definitions').
     */
    public function getWorkflowDefinitionKey(): string;

    /**
     * Get additional context data for workflow routing decisions.
     */
    public function getWorkflowContext(): array;
}
