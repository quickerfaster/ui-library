<?php

namespace QuickerFaster\UILibrary\Services\Approvals;

use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;

/**
 * Workspace-scoped implementation of the ApproverResolver contract.
 *
 * Extends the default Spatie-based resolution with workspace/tenant scoping.
 * When a $workspaceId is provided, user IDs are verified against the
 * workspace and role resolution is constrained to users within that
 * workspace. When $workspaceId is null, behaviour is identical to
 * DefaultApproverResolver.
 *
 * Uses config('auth.providers.users.model') for the User class and
 * config('ui-library.tenancy.column') for the tenancy column, avoiding
 * hardcoded FQCNs so consuming apps can customise both.
 */
class WorkspaceScopedApproverResolver implements ApproverResolver
{
    /**
     * Resolve a mixed list of user IDs and role names into a flat list of
     * user IDs, optionally scoped to a single workspace.
     *
     * Convention:
     *   - int    → already-resolved user ID. When unscoped, passed through
     *              as-is. When scoped, verified against the workspace before
     *              inclusion.
     *   - string → role name; the role model is queried by `name` and every
     *              user holding that role is collected. When a workspace is
     *              supplied, only users belonging to that workspace are
     *              included.
     *
     * @param array<int|string> $roleIds Mixed user IDs (int) and role names (string).
     * @param string|null $workspaceId Optional workspace scope for multi-tenant apps.
     * @return int[] Flat list of resolved user IDs.
     */
    public function resolve(array $roleIds, ?string $workspaceId = null): array
    {
        if ($roleIds === []) {
            return [];
        }

        // No workspace scope → delegate to the same logic as
        // DefaultApproverResolver (global resolution).
        if ($workspaceId === null) {
            return $this->resolveUnscoped($roleIds);
        }

        return $this->resolveScoped($roleIds, $workspaceId);
    }

    /**
     * Resolve without workspace scoping — identical behaviour to
     * DefaultApproverResolver.
     *
     * @param array<int|string> $roleIds
     * @return int[]
     */
    protected function resolveUnscoped(array $roleIds): array
    {
        $userIds = [];
        $roleNames = [];

        foreach ($roleIds as $id) {
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                // Integer (or numeric string) → already-resolved user ID.
                $userIds[] = (int) $id;
            } else {
                // String → role name to resolve.
                $roleNames[] = $id;
            }
        }

        if ($roleNames !== []) {
            $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);

            $roles = $roleModel::query()
                ->whereIn('name', $roleNames)
                ->get();

            foreach ($roles as $role) {
                foreach ($role->users as $user) {
                    $userId = method_exists($user, 'getAuthIdentifier')
                        ? $user->getAuthIdentifier()
                        : ($user->id ?? null);

                    if ($userId !== null) {
                        $userIds[] = (int) $userId;
                    }
                }
            }
        }

        return array_values(array_unique($userIds));
    }

    /**
     * Resolve with workspace scoping.
     *
     * @param array<int|string> $roleIds
     * @param string $workspaceId
     * @return int[]
     */
    protected function resolveScoped(array $roleIds, string $workspaceId): array
    {
        $tenancyColumn = config('ui-library.tenancy.column', 'company_id');
        $userModelClass = config('auth.providers.users.model');

        $userIds = [];
        $roleNames = [];

        foreach ($roleIds as $id) {
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                // Integer → verify the user belongs to the workspace
                // before including them.
                if (
                    $userModelClass !== null
                    && $this->userBelongsToWorkspace(
                        (int) $id,
                        $workspaceId,
                        $tenancyColumn,
                        $userModelClass
                    )
                ) {
                    $userIds[] = (int) $id;
                }
            } else {
                // String → role name to resolve within the workspace.
                $roleNames[] = $id;
            }
        }

        if ($roleNames !== []) {
            $userIds = array_merge(
                $userIds,
                $this->resolveRoleNamesScoped($roleNames, $workspaceId, $tenancyColumn)
            );
        }

        return array_values(array_unique($userIds));
    }

    /**
     * Check whether a user belongs to the given workspace.
     *
     * @param int $userId
     * @param string $workspaceId
     * @param string $tenancyColumn
     * @param string $userModelClass
     * @return bool
     */
    protected function userBelongsToWorkspace(
        int $userId,
        string $workspaceId,
        string $tenancyColumn,
        string $userModelClass
    ): bool {
        return $userModelClass::where('id', $userId)
            ->where($tenancyColumn, $workspaceId)
            ->exists();
    }

    /**
     * Resolve role names to the IDs of users holding those roles within the
     * given workspace.
     *
     * @param string[] $roleNames
     * @param string $workspaceId
     * @param string $tenancyColumn
     * @return int[]
     */
    protected function resolveRoleNamesScoped(
        array $roleNames,
        string $workspaceId,
        string $tenancyColumn
    ): array {
        $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);

        $roles = $roleModel::query()
            ->whereIn('name', $roleNames)
            ->get();

        $userIds = [];

        foreach ($roles as $role) {
            // Query users of this role filtered by the tenancy column so
            // only members of the target workspace are included.
            $users = $role->users()
                ->where($tenancyColumn, $workspaceId)
                ->get();

            foreach ($users as $user) {
                $userId = method_exists($user, 'getAuthIdentifier')
                    ? $user->getAuthIdentifier()
                    : ($user->id ?? null);

                if ($userId !== null) {
                    $userIds[] = (int) $userId;
                }
            }
        }

        return $userIds;
    }
}