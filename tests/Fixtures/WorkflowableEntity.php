<?php

namespace QuickerFaster\UILibrary\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;
use QuickerFaster\UILibrary\Traits\Workflows\HasWorkflow;

/**
 * Minimal Eloquent model for testing the HasWorkflow trait.
 *
 * Implements Workflowable and uses HasWorkflow so the trait's relationship
 * methods and default getWorkflowableId() can be verified in isolation.
 */
class WorkflowableEntity extends Model implements Workflowable
{
    use HasWorkflow;

    protected $table = 'workflowable_entities';

    protected $guarded = [];

    public function getWorkflowDefinitionKey(): string
    {
        return 'test_workflow';
    }

    public function getWorkflowContext(): array
    {
        return [];
    }
}