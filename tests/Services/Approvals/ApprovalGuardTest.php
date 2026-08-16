<?php

namespace QuickerFaster\UILibrary\Tests\Services\Approvals;

use Mockery;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;
use QuickerFaster\UILibrary\Services\Approvals\ApprovalGuard;
use QuickerFaster\UILibrary\Tests\Fixtures\User;
use QuickerFaster\UILibrary\Tests\TestCase;

class ApprovalGuardTest extends TestCase
{
    /** @var \Mockery\MockInterface&ApproverResolver */
    protected $approvers;

    protected ApprovalGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approvers = Mockery::mock(ApproverResolver::class);
        $this->guard = new ApprovalGuard($this->approvers);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // canApprove — authorization for approve/reject actions
    // ------------------------------------------------------------------

    /** @test */
    public function can_approve_returns_true_when_user_id_is_in_resolved_approver_ids(): void
    {
        $user = $this->createUser(['id' => 42]);

        $this->approvers->shouldReceive('resolve')
            ->with(['manager'], null)
            ->andReturn([10, 42, 99]);

        $result = $this->guard->canApprove($user, ['manager']);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_approve_returns_false_when_user_id_not_in_resolved_approver_ids(): void
    {
        $user = $this->createUser(['id' => 77]);

        $this->approvers->shouldReceive('resolve')
            ->with(['manager'], null)
            ->andReturn([10, 42, 99]);

        $result = $this->guard->canApprove($user, ['manager']);

        $this->assertFalse($result);
    }

    /** @test */
    public function can_approve_returns_false_for_null_user(): void
    {
        $result = $this->guard->canApprove(null, ['manager']);

        $this->assertFalse($result);
    }

    /** @test */
    public function can_approve_returns_false_when_user_has_no_identifier(): void
    {
        $user = Mockery::mock();
        $user->shouldReceive('getAuthIdentifier')->andReturn(null);
        // hasAnyRole check — mock doesn't have it, so method_exists returns false.
        // AuthorizationService::isBypassAllowed checks method_exists('hasAnyRole') → false.
        // Then checks email → mock doesn't have getAttribute → method_exists false → returns false.

        $result = $this->guard->canApprove($user, ['manager']);

        $this->assertFalse($result);
    }

    /** @test */
    public function can_approve_passes_workspace_id_to_resolver(): void
    {
        $user = $this->createUser(['id' => 1]);

        $this->approvers->shouldReceive('resolve')
            ->with(['admin'], 'workspace-5')
            ->andReturn([1]);

        $result = $this->guard->canApprove($user, ['admin'], 'workspace-5');

        $this->assertTrue($result);
    }

    // ------------------------------------------------------------------
    // canSubmit — authorization for submit action
    // ------------------------------------------------------------------

    /** @test */
    public function can_submit_returns_true_when_user_is_in_initiator_list(): void
    {
        $user = $this->createUser(['id' => 10]);

        $this->approvers->shouldReceive('resolve')
            ->with([10], null)
            ->andReturn([10]);

        $result = $this->guard->canSubmit($user, [10]);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_submit_returns_false_when_user_not_in_initiator_list(): void
    {
        $user = $this->createUser(['id' => 99]);

        $this->approvers->shouldReceive('resolve')
            ->with([10], null)
            ->andReturn([10]);

        $result = $this->guard->canSubmit($user, [10]);

        $this->assertFalse($result);
    }

    /** @test */
    public function can_submit_returns_false_for_null_user(): void
    {
        $result = $this->guard->canSubmit(null, [10]);

        $this->assertFalse($result);
    }

    // ------------------------------------------------------------------
    // Bypass — super admin / configured bypass roles
    // ------------------------------------------------------------------

    /** @test */
    public function bypass_allows_user_with_super_admin_role_without_resolving_approvers(): void
    {
        $user = new class extends User {
            public function hasAnyRole($roles): bool
            {
                return in_array('super_admin', (array) $roles);
            }
        };

        $user->forceFill([
            'name' => 'Super Admin',
            'email' => 'super-admin@example.com',
            'password' => bcrypt('secret'),
        ]);
        $user->save();

        // ApproverResolver should NOT be called because bypass short-circuits.
        $this->approvers->shouldNotReceive('resolve');

        $result = $this->guard->canApprove($user, ['some_role']);

        $this->assertTrue($result);
    }

    /** @test */
    public function bypass_allows_user_matching_super_admin_email(): void
    {
        // Set the SUPER_ADMIN_EMAIL env to match the user's email.
        config()->set('ui-library.approvals.bypass_roles', []); // no role bypass

        $user = $this->createUser(['email' => 'admin@example.com']);

        // AuthorizationService::isBypassAllowed checks env('SUPER_ADMIN_EMAIL', 'admin@example.com').
        // The default is 'admin@example.com', which matches.
        $this->approvers->shouldNotReceive('resolve');

        $result = $this->guard->canApprove($user, ['some_role']);

        $this->assertTrue($result);
    }

    /** @test */
    public function bypass_does_not_trigger_for_regular_user(): void
    {
        config()->set('ui-library.approvals.bypass_roles', ['super_admin']);

        $user = $this->createUser(['email' => 'regular@example.com']);

        // Regular user — hasAnyRole returns false, email doesn't match SUPER_ADMIN_EMAIL.
        // So bypass is not triggered; resolver is called.
        $this->approvers->shouldReceive('resolve')
            ->with(['some_role'], null)
            ->andReturn([999]);

        $result = $this->guard->canApprove($user, ['some_role']);

        $this->assertFalse($result);
    }

    // ------------------------------------------------------------------
    // Edge cases
    // ------------------------------------------------------------------

    /** @test */
    public function can_act_returns_false_for_empty_role_list(): void
    {
        $user = $this->createUser(['id' => 1]);

        $this->approvers->shouldReceive('resolve')
            ->with([], null)
            ->andReturn([]);

        $result = $this->guard->canApprove($user, []);

        $this->assertFalse($result);
    }

    /** @test */
    public function can_act_handles_mixed_user_ids_and_role_names(): void
    {
        $user = $this->createUser(['id' => 5]);

        // Mixed list: int = user ID, string = role name.
        $this->approvers->shouldReceive('resolve')
            ->with([5, 'manager'], null)
            ->andReturn([5, 10, 20]);

        $result = $this->guard->canApprove($user, [5, 'manager']);

        $this->assertTrue($result);
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
}