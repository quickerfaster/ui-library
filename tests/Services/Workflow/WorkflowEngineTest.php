<?php

namespace QuickerFaster\UILibrary\Tests\Services\Workflow;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Mockery;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;
use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowApproved;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowRecalled;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowRejected;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowSubmitted;
use QuickerFaster\UILibrary\Models\Workflow;
use QuickerFaster\UILibrary\Models\WorkflowAction;
use QuickerFaster\UILibrary\Models\WorkflowDefinition;
use QuickerFaster\UILibrary\Models\WorkflowDefinitionStep;
use QuickerFaster\UILibrary\Models\WorkflowStep;
use QuickerFaster\UILibrary\Services\Approvals\ApprovalGuard;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;
use QuickerFaster\UILibrary\Services\Workflow\WorkflowEngine;
use QuickerFaster\UILibrary\Tests\Fixtures\User;
use QuickerFaster\UILibrary\Tests\TestCase;

class WorkflowEngineTest extends TestCase
{
    protected WorkflowEngine $engine;

    /** @var \Mockery\MockInterface&NotificationService */
    protected $notifications;

    /** @var \Mockery\MockInterface&ApprovalGuard */
    protected $guard;

    /** @var \Mockery\MockInterface&ApproverResolver */
    protected $approvers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifications = Mockery::mock(NotificationService::class);
        $this->guard = Mockery::mock(ApprovalGuard::class);
        $this->approvers = Mockery::mock(ApproverResolver::class);

        $this->engine = new WorkflowEngine(
            $this->notifications,
            $this->guard,
            $this->approvers,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function createUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('secret'),
        ], $attrs));
    }

    protected function mockWorkflowable(string $key = 'test_workflow', int|string $id = 1): Workflowable
    {
        $entity = Mockery::mock(Workflowable::class);
        $entity->shouldReceive('getWorkflowDefinitionKey')->andReturn($key);
        $entity->shouldReceive('getWorkflowableId')->andReturn($id);
        $entity->shouldReceive('getWorkflowContext')->andReturn([]);

        return $entity;
    }

    // ------------------------------------------------------------------
    // getDefinition() — DB-first with config fallback
    // ------------------------------------------------------------------

    /** @test */
    public function get_definition_returns_db_row_when_active_definition_exists(): void
    {
        $definition = WorkflowDefinition::create([
            'key' => 'db_workflow',
            'name' => 'DB Workflow',
            'entity_type' => 'TestEntity',
            'is_active' => true,
        ]);

        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $definition->id,
            'sequence' => 1,
            'tier_type' => 'review',
            'name' => 'Review',
            'resolution_mode' => 'any',
            'assignees' => ['mode' => 'users', 'ids' => [5]],
        ]);

        $result = $this->engine->getDefinition('db_workflow');

        $this->assertIsArray($result);
        $this->assertSame('DB Workflow', $result['name']);
        $this->assertCount(1, $result['steps']);
        $this->assertSame('Review', $result['steps'][0]['name']);
        $this->assertSame([5], $result['steps'][0]['roles']);
    }

    /** @test */
    public function get_definition_skips_inactive_db_row_and_falls_back_to_config(): void
    {
        WorkflowDefinition::create([
            'key' => 'inactive_wf',
            'name' => 'Inactive',
            'entity_type' => 'TestEntity',
            'is_active' => false,
        ]);

        config()->set('ui-library.workflows.definitions.inactive_wf', [
            'label' => 'Config Fallback',
            'steps' => [
                ['name' => 'Config Step', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => [1]],
            ],
        ]);

        $result = $this->engine->getDefinition('inactive_wf');

        $this->assertIsArray($result);
        $this->assertSame('Config Fallback', $result['label']);
        $this->assertCount(1, $result['steps']);
        $this->assertSame('Config Step', $result['steps'][0]['name']);
    }

    /** @test */
    public function get_definition_returns_null_when_neither_db_nor_config_exists(): void
    {
        $result = $this->engine->getDefinition('nonexistent');

        $this->assertNull($result);
    }

    /** @test */
    public function get_definition_separates_initiator_steps_from_approval_steps(): void
    {
        $definition = WorkflowDefinition::create([
            'key' => 'with_initiators',
            'name' => 'With Initiators',
            'entity_type' => 'TestEntity',
            'is_active' => true,
        ]);

        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $definition->id,
            'sequence' => 1,
            'tier_type' => 'initiator',
            'name' => 'Initiator',
            'resolution_mode' => 'any',
            'assignees' => ['mode' => 'users', 'ids' => [10, 20]],
        ]);

        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $definition->id,
            'sequence' => 2,
            'tier_type' => 'review',
            'name' => 'Review',
            'resolution_mode' => 'all',
            'assignees' => ['mode' => 'roles', 'ids' => ['manager']],
        ]);

        $result = $this->engine->getDefinition('with_initiators');

        $this->assertSame([10, 20], $result['initiators']);
        $this->assertCount(1, $result['steps']);
        $this->assertSame('Review', $result['steps'][0]['name']);
        $this->assertSame('all', $result['steps'][0]['approval_mode']);
    }

    // ------------------------------------------------------------------
    // start() — creates WorkflowAction, fires WorkflowSubmitted
    // ------------------------------------------------------------------

    /** @test */
    public function start_creates_workflow_and_steps_and_fires_submitted_event(): void
    {
        Event::fake([WorkflowSubmitted::class, WorkflowApproved::class]);

        $user = $this->createUser();
        Auth::login($user);

        config()->set('ui-library.workflows.definitions.start_test', [
            'label' => 'Start Test',
            'steps' => [
                ['name' => 'Review', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => [5]],
            ],
        ]);

        $this->approvers->shouldReceive('resolve')->with([5], null)->andReturn([5]);

        $entity = $this->mockWorkflowable('start_test');

        $workflow = $this->engine->start($entity);

        $this->assertInstanceOf(Workflow::class, $workflow);
        $this->assertSame('pending', $workflow->status);
        $this->assertNotNull($workflow->current_step);

        $this->assertDatabaseHas('workflow_steps', [
            'workflow_id' => $workflow->id,
            'name' => 'Review',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('workflow_actions', [
            'workflow_id' => $workflow->id,
            'action' => 'submitted',
            'user_id' => $user->id,
        ]);

        Event::assertDispatched(WorkflowSubmitted::class, fn ($e) => $e->workflow->id === $workflow->id);
    }

    /** @test */
    public function start_throws_when_definition_not_found(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Workflow definition 'missing' not found.");

        $entity = $this->mockWorkflowable('missing');
        $this->engine->start($entity);
    }

    /** @test */
    public function start_throws_when_duplicate_active_workflow_exists(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        config()->set('ui-library.workflows.definitions.dup_test', [
            'label' => 'Dup Test',
            'steps' => [
                ['name' => 'Review', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => [5]],
            ],
        ]);

        $this->approvers->shouldReceive('resolve')->with([5], null)->andReturn([5]);

        $entity = $this->mockWorkflowable('dup_test');

        // First start succeeds.
        $this->engine->start($entity);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('An active workflow already exists for this entity.');

        // Second start with same entity should throw.
        $this->engine->start($entity);
    }

    /** @test */
    public function start_auto_approves_when_definition_has_no_approval_steps(): void
    {
        Event::fake([WorkflowSubmitted::class, WorkflowApproved::class]);

        $user = $this->createUser();
        Auth::login($user);

        // Definition with only initiators (no review/authorizer steps).
        config()->set('ui-library.workflows.definitions.auto_approve_test', [
            'label' => 'Auto Approve',
            'initiators' => [10],
            'steps' => [],
        ]);

        $this->guard->shouldReceive('canSubmit')
            ->with($user, [10], null)
            ->andReturn(true);

        $entity = $this->mockWorkflowable('auto_approve_test');

        $workflow = $this->engine->start($entity);

        $this->assertSame('approved', $workflow->status);
        $this->assertNotNull($workflow->completed_at);

        $this->assertDatabaseHas('workflow_actions', [
            'workflow_id' => $workflow->id,
            'action' => 'completed',
        ]);

        Event::assertDispatched(WorkflowApproved::class, fn ($e) => $e->workflow->id === $workflow->id && $e->completed === true);
        Event::assertNotDispatched(WorkflowSubmitted::class);
    }

    /** @test */
    public function start_throws_authorization_exception_when_user_not_in_initiator_list(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        config()->set('ui-library.workflows.definitions.gated_test', [
            'label' => 'Gated',
            'initiators' => [99],
            'steps' => [
                ['name' => 'Review', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => [5]],
            ],
        ]);

        $this->guard->shouldReceive('canSubmit')
            ->with($user, [99], null)
            ->andReturn(false);

        $entity = $this->mockWorkflowable('gated_test');

        $this->expectException(AuthorizationException::class);
        $this->engine->start($entity);
    }

    // ------------------------------------------------------------------
    // approve() — resolves approvers, creates WorkflowAction, fires WorkflowApproved
    // ------------------------------------------------------------------

    /** @test */
    public function approve_advances_step_and_fires_approved_event(): void
    {
        Event::fake([WorkflowApproved::class]);

        $user = $this->createUser();
        Auth::login($user);

        $workflow = $this->createPendingWorkflowWithStep('approve_test', [5]);

        $this->guard->shouldReceive('canApprove')
            ->with($user, [5], null)
            ->andReturn(true);

        // ApproverResolver called for notification recipients after approval.
        $this->approvers->shouldReceive('resolve')->andReturn([]);

        $this->engine->approve($workflow);

        $this->assertDatabaseHas('workflow_steps', [
            'id' => $workflow->currentStep->id,
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);

        $this->assertDatabaseHas('workflow_actions', [
            'workflow_id' => $workflow->id,
            'step_id' => $workflow->currentStep->id,
            'action' => 'approved',
            'user_id' => $user->id,
        ]);

        Event::assertDispatched(WorkflowApproved::class, fn ($e) => $e->workflow->id === $workflow->id);
    }

    /** @test */
    public function approve_completes_workflow_when_last_step(): void
    {
        Event::fake([WorkflowApproved::class]);

        $user = $this->createUser();
        Auth::login($user);

        $workflow = $this->createPendingWorkflowWithStep('last_step_test', [5]);

        $this->guard->shouldReceive('canApprove')
            ->with($user, [5], null)
            ->andReturn(true);

        $this->approvers->shouldReceive('resolve')->andReturn([]);

        $this->engine->approve($workflow);

        $workflow->refresh();

        $this->assertSame('approved', $workflow->status);
        $this->assertNotNull($workflow->completed_at);

        $this->assertDatabaseHas('workflow_actions', [
            'workflow_id' => $workflow->id,
            'action' => 'completed',
        ]);

        Event::assertDispatched(WorkflowApproved::class, fn ($e) => $e->completed === true);
    }

    /** @test */
    public function approve_throws_when_workflow_not_pending(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $workflow = Workflow::create([
            'workflowable_type' => 'test',
            'workflowable_id' => 1,
            'definition_key' => 'done',
            'status' => 'approved',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Workflow is not pending.');

        $this->engine->approve($workflow);
    }

    /** @test */
    public function approve_throws_authorization_exception_when_user_not_authorized(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $workflow = $this->createPendingWorkflowWithStep('unauth_test', [99]);

        $this->guard->shouldReceive('canApprove')
            ->with($user, [99], null)
            ->andReturn(false);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You are not authorized to approve this workflow step.');

        $this->engine->approve($workflow);
    }

    /** @test */
    public function approve_all_mode_requires_every_assignee(): void
    {
        Event::fake([WorkflowApproved::class]);

        $user1 = $this->createUser(['email' => 'user1@example.com']);
        $user2 = $this->createUser(['email' => 'user2@example.com']);

        $workflow = $this->createPendingWorkflowWithStep('all_mode_test', [5, 6], 'all');

        // The approver resolver may be called multiple times during the
        // all-mode flow (once for required-id resolution, and again for
        // notification recipient resolution after the transaction).
        $this->approvers->shouldReceive('resolve')
            ->andReturn([10, 20]);

        // First approval — partial.
        Auth::login($user1);

        $this->guard->shouldReceive('canApprove')
            ->with($user1, [5, 6], null)
            ->andReturn(true);

        $this->engine->approve($workflow);

        // Step should still be pending after first approval.
        $this->assertDatabaseHas('workflow_steps', [
            'id' => $workflow->currentStep->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('workflow_actions', [
            'workflow_id' => $workflow->id,
            'step_id' => $workflow->currentStep->id,
            'action' => 'approved',
            'user_id' => $user1->id,
        ]);

        Event::assertDispatched(WorkflowApproved::class, fn ($e) => $e->completed === false);

        // Second approval — should complete the step.
        Auth::login($user2);

        $this->guard->shouldReceive('canApprove')
            ->with($user2, [5, 6], null)
            ->andReturn(true);

        $this->engine->approve($workflow);

        $this->assertDatabaseHas('workflow_steps', [
            'id' => $workflow->currentStep->id,
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function approve_all_mode_prevents_duplicate_approval_by_same_user(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $workflow = $this->createPendingWorkflowWithStep('dup_approve_test', [5, 6], 'all');

        $this->guard->shouldReceive('canApprove')
            ->with($user, [5, 6], null)
            ->andReturn(true);

        $this->approvers->shouldReceive('resolve')
            ->with([5, 6], null)
            ->andReturn([10, 20]);

        // First approval succeeds.
        $this->engine->approve($workflow);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You have already approved this step.');

        // Second approval by same user should throw.
        $this->engine->approve($workflow);
    }

    // ------------------------------------------------------------------
    // reject() — creates WorkflowAction, fires WorkflowRejected
    // ------------------------------------------------------------------

    /** @test */
    public function reject_marks_step_and_workflow_rejected_and_fires_event(): void
    {
        Event::fake([WorkflowRejected::class]);

        $user = $this->createUser();
        Auth::login($user);

        $workflow = $this->createPendingWorkflowWithStep('reject_test', [5]);

        $this->guard->shouldReceive('canApprove')
            ->with($user, [5], null)
            ->andReturn(true);

        $this->engine->reject($workflow, 'Not good enough');

        $this->assertDatabaseHas('workflow_steps', [
            'id' => $workflow->currentStep->id,
            'status' => 'rejected',
            'comments' => 'Not good enough',
        ]);

        $this->assertDatabaseHas('workflows', [
            'id' => $workflow->id,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseHas('workflow_actions', [
            'workflow_id' => $workflow->id,
            'step_id' => $workflow->currentStep->id,
            'action' => 'rejected',
            'user_id' => $user->id,
        ]);

        Event::assertDispatched(WorkflowRejected::class, fn ($e) => $e->workflow->id === $workflow->id && $e->reason === 'Not good enough');
    }

    /** @test */
    public function reject_throws_when_workflow_not_pending(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $workflow = Workflow::create([
            'workflowable_type' => 'test',
            'workflowable_id' => 1,
            'definition_key' => 'done',
            'status' => 'approved',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Workflow is not pending.');

        $this->engine->reject($workflow);
    }

    /** @test */
    public function reject_throws_authorization_exception_when_user_not_authorized(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $workflow = $this->createPendingWorkflowWithStep('reject_unauth', [99]);

        $this->guard->shouldReceive('canApprove')
            ->with($user, [99], null)
            ->andReturn(false);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You are not authorized to reject this workflow step.');

        $this->engine->reject($workflow);
    }

    // ------------------------------------------------------------------
    // recall() — creates WorkflowAction, fires WorkflowRecalled
    // ------------------------------------------------------------------

    /** @test */
    public function recall_cancels_workflow_and_fires_recalled_event(): void
    {
        Event::fake([WorkflowRecalled::class]);

        $user = $this->createUser();
        Auth::login($user);

        $workflow = $this->createPendingWorkflowWithStep('recall_test', [5]);

        $this->approvers->shouldReceive('resolve')->andReturn([]);

        $this->engine->recall($workflow);

        $this->assertDatabaseHas('workflows', [
            'id' => $workflow->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('workflow_actions', [
            'workflow_id' => $workflow->id,
            'action' => 'recalled',
            'user_id' => $user->id,
        ]);

        Event::assertDispatched(WorkflowRecalled::class, fn ($e) => $e->workflow->id === $workflow->id);
    }

    /** @test */
    public function recall_throws_when_workflow_not_pending(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $workflow = Workflow::create([
            'workflowable_type' => 'test',
            'workflowable_id' => 1,
            'definition_key' => 'done',
            'status' => 'approved',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only pending workflows can be recalled.');

        $this->engine->recall($workflow);
    }

    // ------------------------------------------------------------------
    // notifyTransition() — notification dispatch
    // ------------------------------------------------------------------

    /** @test */
    public function notify_transition_dispatches_notification_when_config_is_present(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        // Create a DB definition with notifications enabled.
        $definition = WorkflowDefinition::create([
            'key' => 'notify_test',
            'name' => 'Notify Test',
            'entity_type' => 'TestEntity',
            'is_active' => true,
            'notifications' => [
                'enabled' => true,
                'types' => [
                    'submitted' => 'workflow_submitted',
                    'approved' => 'workflow_approved',
                    'rejected' => 'workflow_rejected',
                    'recalled' => 'workflow_recalled',
                ],
            ],
        ]);

        $workflow = $this->createPendingWorkflowWithStep('notify_test', [5]);

        $this->guard->shouldReceive('canApprove')
            ->with($user, [5], null)
            ->andReturn(true);

        // ApproverResolver returns a recipient user ID.
        $this->approvers->shouldReceive('resolve')
            ->with([5], null)
            ->andReturn([$user->id]);

        // NotificationService::dispatch should be called.
        $this->notifications->shouldReceive('dispatch')
            ->once()
            ->with(
                Mockery::on(fn ($notifiable) => $notifiable->getNotifiableId() === $user->id),
                'workflow_approved',
                Mockery::type('array'),
            )
            ->andReturn(['database' => true]);

        $this->engine->approve($workflow);
    }

    /** @test */
    public function notify_transition_skips_when_notifications_disabled(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        WorkflowDefinition::create([
            'key' => 'no_notify',
            'name' => 'No Notify',
            'entity_type' => 'TestEntity',
            'is_active' => true,
            'notifications' => [
                'enabled' => false,
                'types' => [
                    'approved' => 'workflow_approved',
                ],
            ],
        ]);

        $workflow = $this->createPendingWorkflowWithStep('no_notify', [5]);

        $this->guard->shouldReceive('canApprove')
            ->with($user, [5], null)
            ->andReturn(true);

        $this->approvers->shouldReceive('resolve')->andReturn([$user->id]);

        // dispatch should NOT be called because notifications.enabled is false.
        $this->notifications->shouldNotReceive('dispatch');

        $this->engine->approve($workflow);
    }

    /** @test */
    public function notify_transition_skips_when_event_type_is_null(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        WorkflowDefinition::create([
            'key' => 'null_type',
            'name' => 'Null Type',
            'entity_type' => 'TestEntity',
            'is_active' => true,
            'notifications' => [
                'enabled' => true,
                'types' => [
                    'approved' => null, // toggle off for approved
                ],
            ],
        ]);

        $workflow = $this->createPendingWorkflowWithStep('null_type', [5]);

        $this->guard->shouldReceive('canApprove')
            ->with($user, [5], null)
            ->andReturn(true);

        $this->approvers->shouldReceive('resolve')->andReturn([$user->id]);

        $this->notifications->shouldNotReceive('dispatch');

        $this->engine->approve($workflow);
    }

    // ------------------------------------------------------------------
    // Helpers for creating test data
    // ------------------------------------------------------------------

    protected function createPendingWorkflowWithStep(string $key, array $roles, string $approvalMode = 'any'): Workflow
    {
        $user = Auth::user();

        $workflow = Workflow::create([
            'workflowable_type' => 'test_entity',
            'workflowable_id' => 1,
            'definition_key' => $key,
            'status' => 'pending',
            'submitted_by' => $user?->id,
            'submitted_at' => now(),
            'context' => [],
        ]);

        $step = WorkflowStep::create([
            'workflow_id' => $workflow->id,
            'name' => 'Test Step',
            'sequence' => 1,
            'step_type' => 'approval',
            'approval_mode' => $approvalMode,
            'roles' => $roles,
            'status' => 'pending',
        ]);

        $workflow->current_step = $step->id;
        $workflow->save();

        return $workflow->fresh()->load('currentStep');
    }
}