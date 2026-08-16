<?php

namespace QuickerFaster\UILibrary\Services\Approvals;

use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;
use QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService;

class ApprovalGuard
{
    public function __construct(
        protected ApproverResolver $approvers
    ) {
    }

    /**
     * Determine whether the given user can approve a step for the given roles.
     *
     * @param mixed $user
     * @param array $roleIds
     * @param string|null $workspaceId
     * @return bool
     */
    public function canApprove($user, array $roleIds, ?string $workspaceId = null): bool
    {
        return $this->canAct($user, $roleIds, $workspaceId);
    }

    /**
     * Determine whether the given user can submit for the given initiator roles.
     *
     * @param mixed $user
     * @param array $roleIds
     * @param string|null $workspaceId
     * @return bool
     */
    public function canSubmit($user, array $roleIds, ?string $workspaceId = null): bool
    {
        return $this->canAct($user, $roleIds, $workspaceId);
    }

    /**
     * Resolve the role IDs to user IDs and check whether the user is among them.
     *
     * @param mixed $user
     * @param array $roleIds
     * @param string|null $workspaceId
     * @return bool
     */
    protected function canAct($user, array $roleIds, ?string $workspaceId = null): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isBypassAllowed($user)) {
            return true;
        }

        $userId = method_exists($user, 'getAuthIdentifier')
            ? $user->getAuthIdentifier()
            : ($user->id ?? null);

        if ($userId === null) {
            return false;
        }

        $resolvedIds = $this->approvers->resolve($roleIds, $workspaceId);

        return in_array((string) $userId, array_map('strval', $resolvedIds), true);
    }

    /**
     * Super admins (and any configured bypass roles) bypass role resolution.
     *
     * @param mixed $user
     * @return bool
     */
    protected function isBypassAllowed($user): bool
    {
        $bypassRoles = (array) config('ui-library.approvals.bypass_roles', ['super_admin']);

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($bypassRoles)) {
            return true;
        }

        // Fall back to the library's central bypass, which also honours
        // the SUPER_ADMIN_EMAIL environment override. The central service
        // accepts only Authenticatable instances, so guard against mixed
        // user objects (e.g. anonymous identifiers) before delegating.
        if (! $user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return false;
        }

        return AuthorizationService::isBypassAllowed($user);
    }
}
