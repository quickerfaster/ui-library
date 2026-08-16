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
     * This maps to a workflow definition key (resolved DB-first from
     * `workflow_definitions`, falling back to
     * `config('ui-library.workflows.definitions')`).
     */
    public function getWorkflowDefinitionKey(): string;

    /**
     * Get additional context data for workflow routing decisions.
     */
    public function getWorkflowContext(): array;
}
