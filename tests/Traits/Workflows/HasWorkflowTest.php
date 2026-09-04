<?php

namespace QuickerFaster\UILibrary\Tests\Traits\Workflows;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Tests\Fixtures\WorkflowableEntity;
use QuickerFaster\UILibrary\Tests\TestCase;

class HasWorkflowTest extends TestCase
{
    protected function createEntity(): WorkflowableEntity
    {
        return WorkflowableEntity::create(['name' => 'Test Entity']);
    }

    protected function createWorkflow(WorkflowableEntity $entity, string $status = 'pending'): Workflow
    {
        return $entity->workflows()->create([
            'definition_key' => 'test_workflow',
            'status' => $status,
        ]);
    }

    /** @test */
    public function workflow_returns_a_morph_one_relationship(): void
    {
        $entity = $this->createEntity();

        $this->assertInstanceOf(MorphOne::class, $entity->workflow());
    }

    /** @test */
    public function workflows_returns_a_morph_many_relationship(): void
    {
        $entity = $this->createEntity();

        $this->assertInstanceOf(MorphMany::class, $entity->workflows());
    }

    /** @test */
    public function active_workflow_resolves_the_latest_pending_workflow(): void
    {
        $entity = $this->createEntity();

        $older = $this->createWorkflow($entity);
        $older->forceFill(['created_at' => now()->subMinutes(10)])->save();

        $latest = $this->createWorkflow($entity);

        $this->assertSame($latest->id, $entity->activeWorkflow()->first()->id);
    }

    /** @test */
    public function active_workflow_ignores_non_pending_workflows(): void
    {
        $entity = $this->createEntity();

        $this->createWorkflow($entity, 'approved');

        $this->assertNull($entity->activeWorkflow()->first());
    }

    /** @test */
    public function is_under_approval_reflects_pending_workflow_state(): void
    {
        $entity = $this->createEntity();

        $this->assertFalse($entity->isUnderApproval());

        $this->createWorkflow($entity, 'pending');

        $this->assertTrue($entity->isUnderApproval());
    }

    /** @test */
    public function get_workflowable_id_defaults_to_the_model_key(): void
    {
        $entity = $this->createEntity();

        $this->assertSame($entity->getKey(), $entity->getWorkflowableId());
    }
}