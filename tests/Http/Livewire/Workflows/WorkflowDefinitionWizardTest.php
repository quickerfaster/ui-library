<?php

namespace QuickerFaster\UILibrary\Tests\Http\Livewire\Workflows;

use Livewire\Livewire;
use QuickerFaster\UILibrary\Http\Livewire\Workflows\ReviewerChainBuilder;
use QuickerFaster\UILibrary\Http\Livewire\Workflows\WorkflowDefinitionWizard;
use QuickerFaster\UILibrary\Models\WorkflowDefinition;
use QuickerFaster\UILibrary\Models\WorkflowDefinitionStep;
use QuickerFaster\UILibrary\Tests\TestCase;

class WorkflowDefinitionWizardTest extends TestCase
{
    // ------------------------------------------------------------------
    // Step navigation
    // ------------------------------------------------------------------

    /** @test */
    public function next_advances_to_next_step_when_details_are_valid(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Expense Approval')
            ->set('entityType', 'Expense')
            ->call('next')
            ->assertSet('currentStep', 1);
    }

    /** @test */
    public function previous_returns_to_previous_step(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Expense Approval')
            ->set('entityType', 'Expense')
            ->call('next')
            ->assertSet('currentStep', 1)
            ->call('previous')
            ->assertSet('currentStep', 0);
    }

    /** @test */
    public function go_to_step_jumps_to_specified_step(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->call('goToStep', 4)
            ->assertSet('currentStep', 4);
    }

    /** @test */
    public function go_to_step_ignores_out_of_bounds_index(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->call('goToStep', 99)
            ->assertSet('currentStep', 0);
    }

    // ------------------------------------------------------------------
    // Step validation rules
    // ------------------------------------------------------------------

    /** @test */
    public function next_stays_on_details_step_when_name_is_empty(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('entityType', 'Expense')
            ->call('next')
            ->assertSet('currentStep', 0)
            ->assertHasErrors('workflowName');
    }

    /** @test */
    public function next_stays_on_details_step_when_entity_type_is_empty(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Expense Approval')
            ->call('next')
            ->assertSet('currentStep', 0)
            ->assertHasErrors('entityType');
    }

    /** @test */
    public function next_validates_workflow_key_uniqueness(): void
    {
        WorkflowDefinition::create([
            'key' => 'existing_key',
            'name' => 'Existing',
            'entity_type' => 'Test',
            'is_active' => true,
        ]);

        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Existing Key')
            ->set('workflowKey', 'existing_key')
            ->set('entityType', 'Test')
            ->call('next')
            ->assertSet('currentStep', 0)
            ->assertHasErrors('workflowKey');
    }

    /** @test */
    public function initiator_step_requires_at_least_one_assignee(): void
    {
        $component = Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Test')
            ->set('entityType', 'Test')
            ->call('next') // step 0 → 1
            ->assertSet('currentStep', 1);

        // initiators is empty by default; calling next should fail.
        $component->call('next')
            ->assertSet('currentStep', 1)
            ->assertHasErrors('initiatorAssignees');
    }

    /** @test */
    public function review_step_with_name_requires_assignees(): void
    {
        $component = Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Test')
            ->set('entityType', 'Test')
            ->set('initiators', [['type' => 'user', 'id' => 1, 'label' => 'Alice']])
            ->call('next') // step 0 → 1
            ->call('next') // step 1 → 2 (initiators valid)
            ->assertSet('currentStep', 2);

        // Set a review step with a name but no assignees.
        $component->set('reviewSteps', [
            ['name' => 'Finance Review', 'resolution_mode' => 'any', 'assignees' => []],
        ]);

        $component->call('next')
            ->assertSet('currentStep', 2)
            ->assertHasErrors('reviewSteps.0.assignees');
    }

    /** @test */
    public function authorizer_step_requires_at_least_one_assignee(): void
    {
        $component = Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Test')
            ->set('entityType', 'Test')
            ->set('initiators', [['type' => 'user', 'id' => 1, 'label' => 'Alice']])
            ->set('reviewSteps', [['name' => '', 'resolution_mode' => 'any', 'assignees' => []]])
            ->call('next') // step 0 → 1
            ->call('next') // step 1 → 2
            ->call('next') // step 2 → 3 (empty review step skipped)
            ->assertSet('currentStep', 3);

        // authorizers is empty; next should fail.
        $component->call('next')
            ->assertSet('currentStep', 3)
            ->assertHasErrors('authorizerAssignees');
    }

    /** @test */
    public function finish_validates_required_fields_before_saving(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->call('goToStep', 4)
            ->call('finish')
            ->assertSet('currentStep', 0) // redirected to step 0 for missing name
            ->assertHasErrors('workflowName');
    }

    // ------------------------------------------------------------------
    // Workflow key auto-slug
    // ------------------------------------------------------------------

    /** @test */
    public function workflow_key_is_auto_slugged_from_name(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Purchase Order Approval')
            ->assertSet('workflowKey', 'purchase_order_approval');
    }

    /** @test */
    public function workflow_key_is_not_overwritten_after_manual_edit(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Purchase Order Approval')
            ->set('workflowKey', 'po_approval')
            ->set('workflowName', 'Changed Name')
            ->assertSet('workflowKey', 'po_approval');
    }

    // ------------------------------------------------------------------
    // Reviewer chain builder logic
    // ------------------------------------------------------------------

    /** @test */
    public function reviewer_chain_builder_adds_step(): void
    {
        Livewire::test(ReviewerChainBuilder::class)
            ->assertSet('value', [])
            ->call('addStep')
            ->assertSet('value', [['name' => '', 'resolution_mode' => 'any', 'assignees' => []]]);
    }

    /** @test */
    public function reviewer_chain_builder_removes_step(): void
    {
        Livewire::test(ReviewerChainBuilder::class)
            ->set('value', [
                ['name' => 'Step 1', 'resolution_mode' => 'any', 'assignees' => []],
                ['name' => 'Step 2', 'resolution_mode' => 'all', 'assignees' => []],
            ])
            ->call('removeStep', 0)
            ->assertSet('value', [
                ['name' => 'Step 2', 'resolution_mode' => 'all', 'assignees' => []],
            ]);
    }

    /** @test */
    public function reviewer_chain_builder_moves_step_up(): void
    {
        Livewire::test(ReviewerChainBuilder::class)
            ->set('value', [
                ['name' => 'Step 1', 'resolution_mode' => 'any', 'assignees' => []],
                ['name' => 'Step 2', 'resolution_mode' => 'all', 'assignees' => []],
            ])
            ->call('moveStep', 1, 'up')
            ->assertSet('value.0.name', 'Step 2')
            ->assertSet('value.1.name', 'Step 1');
    }

    /** @test */
    public function reviewer_chain_builder_moves_step_down(): void
    {
        Livewire::test(ReviewerChainBuilder::class)
            ->set('value', [
                ['name' => 'Step 1', 'resolution_mode' => 'any', 'assignees' => []],
                ['name' => 'Step 2', 'resolution_mode' => 'all', 'assignees' => []],
            ])
            ->call('moveStep', 0, 'down')
            ->assertSet('value.0.name', 'Step 2')
            ->assertSet('value.1.name', 'Step 1');
    }

    /** @test */
    public function reviewer_chain_builder_adds_assignee(): void
    {
        Livewire::test(ReviewerChainBuilder::class)
            ->set('value', [
                ['name' => 'Review', 'resolution_mode' => 'any', 'assignees' => []],
            ])
            ->call('addAssignee', 0, 'user', 42, 'Bob')
            ->assertSet('value.0.assignees', [
                ['type' => 'user', 'id' => 42, 'label' => 'Bob'],
            ]);
    }

    /** @test */
    public function reviewer_chain_builder_removes_assignee(): void
    {
        Livewire::test(ReviewerChainBuilder::class)
            ->set('value', [
                [
                    'name' => 'Review',
                    'resolution_mode' => 'any',
                    'assignees' => [
                        ['type' => 'user', 'id' => 1, 'label' => 'Alice'],
                        ['type' => 'user', 'id' => 2, 'label' => 'Bob'],
                    ],
                ],
            ])
            ->call('removeAssignee', 0, 0)
            ->assertSet('value.0.assignees', [
                ['type' => 'user', 'id' => 2, 'label' => 'Bob'],
            ]);
    }

    /** @test */
    public function reviewer_chain_builder_prevents_duplicate_assignee(): void
    {
        Livewire::test(ReviewerChainBuilder::class)
            ->set('value', [
                [
                    'name' => 'Review',
                    'resolution_mode' => 'any',
                    'assignees' => [
                        ['type' => 'user', 'id' => 1, 'label' => 'Alice'],
                    ],
                ],
            ])
            ->call('addAssignee', 0, 'user', 1, 'Alice')
            ->assertSet('value.0.assignees', [
                ['type' => 'user', 'id' => 1, 'label' => 'Alice'],
            ]);
    }

    // ------------------------------------------------------------------
    // Notification toggle handling
    // ------------------------------------------------------------------

    /** @test */
    public function notification_toggles_are_persisted_in_saved_definition(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Notify Test')
            ->set('entityType', 'Test')
            ->set('workflowKey', 'notify_test')
            ->set('initiators', [['type' => 'user', 'id' => 1, 'label' => 'Alice']])
            ->set('authorizers', [['type' => 'role', 'id' => 'manager', 'label' => 'Manager']])
            ->set('notifyOnSubmitted', false)
            ->set('notifyOnApproved', true)
            ->set('notifyOnRejected', false)
            ->set('notifyOnRecalled', true)
            ->call('goToStep', 4)
            ->call('finish');

        $definition = WorkflowDefinition::where('key', 'notify_test')->first();

        $this->assertNotNull($definition);
        $this->assertIsArray($definition->notifications);
        $this->assertTrue($definition->notifications['enabled']);
        $this->assertNull($definition->notifications['types']['submitted']);
        $this->assertSame('workflow_approved', $definition->notifications['types']['approved']);
        $this->assertNull($definition->notifications['types']['rejected']);
        $this->assertSame('workflow_recalled', $definition->notifications['types']['recalled']);
    }

    /** @test */
    public function notifications_disabled_when_all_toggles_are_off(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'No Notify')
            ->set('entityType', 'Test')
            ->set('workflowKey', 'no_notify')
            ->set('initiators', [['type' => 'user', 'id' => 1, 'label' => 'Alice']])
            ->set('authorizers', [['type' => 'role', 'id' => 'manager', 'label' => 'Manager']])
            ->set('notifyOnSubmitted', false)
            ->set('notifyOnApproved', false)
            ->set('notifyOnRejected', false)
            ->set('notifyOnRecalled', false)
            ->call('goToStep', 4)
            ->call('finish');

        $definition = WorkflowDefinition::where('key', 'no_notify')->first();

        $this->assertNotNull($definition);
        $this->assertFalse($definition->notifications['enabled']);
    }

    // ------------------------------------------------------------------
    // Edit pre-load via query string
    // ------------------------------------------------------------------

    /** @test */
    public function edit_pre_loads_definition_via_query_string(): void
    {
        $definition = WorkflowDefinition::create([
            'key' => 'edit_test',
            'name' => 'Edit Test',
            'description' => 'A test definition',
            'entity_type' => 'TestEntity',
            'is_active' => true,
            'notifications' => [
                'enabled' => true,
                'types' => [
                    'submitted' => 'workflow_submitted',
                    'approved' => null,
                    'rejected' => 'workflow_rejected',
                    'recalled' => null,
                ],
            ],
        ]);

        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $definition->id,
            'sequence' => 1,
            'tier_type' => 'initiator',
            'name' => 'Initiator',
            'resolution_mode' => 'any',
            'assignees' => ['mode' => 'users', 'ids' => [10]],
        ]);

        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $definition->id,
            'sequence' => 2,
            'tier_type' => 'authorizer',
            'name' => 'Authorizer',
            'resolution_mode' => 'any',
            'assignees' => ['mode' => 'roles', 'ids' => ['manager']],
        ]);

        Livewire::withQueryParams(['definitionId' => $definition->id])
            ->test(WorkflowDefinitionWizard::class)
            ->assertSet('definitionId', $definition->id)
            ->assertSet('workflowName', 'Edit Test')
            ->assertSet('workflowKey', 'edit_test')
            ->assertSet('entityType', 'TestEntity')
            ->assertSet('isActive', true)
            ->assertSet('notifyOnSubmitted', true)
            ->assertSet('notifyOnApproved', false)
            ->assertSet('notifyOnRejected', true)
            ->assertSet('notifyOnRecalled', false)
            ->assertSet('initiators', [
                ['type' => 'user', 'id' => 10, 'label' => 'User #10'],
            ])
            ->assertSet('authorizers', [
                ['type' => 'role', 'id' => 'manager', 'label' => 'manager'],
            ]);
    }

    // ------------------------------------------------------------------
    // Final submission creates a WorkflowDefinition
    // ------------------------------------------------------------------

    /** @test */
    public function final_submission_creates_workflow_definition_with_steps(): void
    {
        Livewire::test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Full Workflow')
            ->set('entityType', 'Document')
            ->set('workflowKey', 'full_workflow')
            ->set('workflowDescription', 'A complete workflow definition')
            ->set('isActive', true)
            ->set('initiators', [
                ['type' => 'user', 'id' => 1, 'label' => 'Alice'],
                ['type' => 'role', 'id' => 'editor', 'label' => 'Editor'],
            ])
            ->set('reviewSteps', [
                [
                    'name' => 'Peer Review',
                    'resolution_mode' => 'all',
                    'assignees' => [
                        ['type' => 'user', 'id' => 2, 'label' => 'Bob'],
                    ],
                ],
            ])
            ->set('authorizers', [
                ['type' => 'role', 'id' => 'manager', 'label' => 'Manager'],
            ])
            ->call('goToStep', 4)
            ->call('finish');

        $definition = WorkflowDefinition::where('key', 'full_workflow')->first();

        $this->assertNotNull($definition);
        $this->assertSame('Full Workflow', $definition->name);
        $this->assertSame('Document', $definition->entity_type);
        $this->assertSame('A complete workflow definition', $definition->description);
        $this->assertTrue($definition->is_active);

        // Steps: initiator (seq 1), review (seq 2), authorizer (seq 3).
        $steps = $definition->steps()->orderBy('sequence')->get();

        $this->assertCount(3, $steps);

        $this->assertSame('initiator', $steps[0]->tier_type);
        $this->assertSame('Initiator', $steps[0]->name);
        $this->assertSame('mixed', $steps[0]->assignees['mode']);
        $this->assertSame([1, 'editor'], $steps[0]->assignees['ids']);

        $this->assertSame('review', $steps[1]->tier_type);
        $this->assertSame('Peer Review', $steps[1]->name);
        $this->assertSame('all', $steps[1]->resolution_mode);

        $this->assertSame('authorizer', $steps[2]->tier_type);
        $this->assertSame('Authorizer', $steps[2]->name);
        $this->assertSame('any', $steps[2]->resolution_mode);
    }

    /** @test */
    public function final_submission_updates_existing_definition_when_editing(): void
    {
        $definition = WorkflowDefinition::create([
            'key' => 'update_test',
            'name' => 'Old Name',
            'entity_type' => 'OldEntity',
            'is_active' => false,
        ]);

        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $definition->id,
            'sequence' => 1,
            'tier_type' => 'initiator',
            'name' => 'Initiator',
            'resolution_mode' => 'any',
            'assignees' => ['mode' => 'users', 'ids' => [1]],
        ]);

        Livewire::withQueryParams(['definitionId' => $definition->id])
            ->test(WorkflowDefinitionWizard::class)
            ->set('workflowName', 'Updated Name')
            ->set('entityType', 'NewEntity')
            ->set('authorizers', [['type' => 'user', 'id' => 99, 'label' => 'Zoe']])
            ->call('goToStep', 4)
            ->call('finish');

        $definition->refresh();

        $this->assertSame('Updated Name', $definition->name);
        $this->assertSame('NewEntity', $definition->entity_type);

        // Old steps deleted; new steps: initiator (from pre-load) + authorizer.
        $steps = $definition->steps()->orderBy('sequence')->get();
        $this->assertCount(2, $steps);
        $this->assertSame('initiator', $steps[0]->tier_type);
        $this->assertSame('authorizer', $steps[1]->tier_type);
    }

    // ------------------------------------------------------------------
    // Pipeline nodes (summary)
    // ------------------------------------------------------------------

    /** @test */
    public function pipeline_nodes_includes_initiator_review_and_authorizer(): void
    {
        $component = Livewire::test(WorkflowDefinitionWizard::class)
            ->set('initiators', [['type' => 'user', 'id' => 1, 'label' => 'Alice']])
            ->set('reviewSteps', [
                ['name' => 'Peer Review', 'resolution_mode' => 'all', 'assignees' => [
                    ['type' => 'user', 'id' => 2, 'label' => 'Bob'],
                ]],
            ])
            ->set('authorizers', [['type' => 'role', 'id' => 'manager', 'label' => 'Manager']]);

        $nodes = $component->instance()->pipelineNodes();

        $this->assertCount(3, $nodes);
        $this->assertSame('Initiator', $nodes[0]['label']);
        $this->assertSame('Peer Review', $nodes[1]['label']);
        $this->assertSame('All must review', $nodes[1]['resolution']);
        $this->assertSame('Authorizer', $nodes[2]['label']);
        $this->assertSame('Any one can approve', $nodes[2]['resolution']);
    }

    /** @test */
    public function pipeline_nodes_skips_empty_review_steps(): void
    {
        $component = Livewire::test(WorkflowDefinitionWizard::class)
            ->set('initiators', [['type' => 'user', 'id' => 1, 'label' => 'Alice']])
            ->set('reviewSteps', [
                ['name' => '', 'resolution_mode' => 'any', 'assignees' => []],
            ])
            ->set('authorizers', [['type' => 'role', 'id' => 'manager', 'label' => 'Manager']]);

        $nodes = $component->instance()->pipelineNodes();

        $this->assertCount(2, $nodes);
        $this->assertSame('Initiator', $nodes[0]['label']);
        $this->assertSame('Authorizer', $nodes[1]['label']);
    }
}