<?php

namespace QuickerFaster\UILibrary\Services\Approvals;

use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;

/**
 * Default Spatie-based implementation of the ApproverResolver contract.
 *
 * Resolves role names/IDs to the user IDs of the users currently holding those
 * roles. Consuming applications can override this by binding their own
 * implementation against the ApproverResolver contract.
 *
 * The $workspaceId argument is accepted for API compatibility but ignored by
 * this default implementation — the library itself has no workspace/company
 * model, so any workspace scoping must be supplied by a consuming application.
 */
class DefaultApproverResolver implements ApproverResolver
{
    /**
     * Resolve a mixed list of user IDs and role names into a flat list of
     * user IDs.
     *
     * Convention (Bug 4 fix):
     *   - int    → already-resolved user ID (passed through as-is).
     *   - string → role name; the role model is queried by `name` and
     *              every user holding that role is collected.
     *
     * @param array<int|string> $roleIds Mixed user IDs (int) and role names (string).
     * @param string|null $workspaceId Accepted for API compatibility; ignored by default.
     * @return int[] Flat list of resolved user IDs.
     */
    public function resolve(array $roleIds, ?string $workspaceId = null): array
    {
        if ($roleIds === []) {
            return [];
        }

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
}
