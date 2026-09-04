{{--
    User Groups page for the Users context group.
    
    This view resolves the /admin/user-groups catch-all route.
    Full user group management functionality will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.user_groups" context="Users" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">User Groups</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Create and manage user groups for streamlined permission assignment. Full user group management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>