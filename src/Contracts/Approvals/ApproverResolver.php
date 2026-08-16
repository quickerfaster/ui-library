<?php

namespace QuickerFaster\UILibrary\Contracts\Approvals;

interface ApproverResolver
{
    /**
     * Resolve a mixed list of user IDs and role names into a flat list of
     * user IDs who can approve (or submit).
     *
     * Convention:
     *   - int    → already-resolved user ID (passed through as-is).
     *   - string → role name; implementations should resolve the role to
     *              the user IDs of every user holding that role.
     *
     * @param array<int|string> $roleIds Mixed user IDs (int) and role names (string).
     * @param string|null $workspaceId Optional workspace scope for multi-tenant apps.
     * @return int[] Flat list of resolved user IDs.
     */
    public function resolve(array $roleIds, ?string $workspaceId = null): array;
}
