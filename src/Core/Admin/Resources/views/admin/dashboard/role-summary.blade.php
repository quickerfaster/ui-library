{{--
    Role Summary page for the Dashboard context group.
    
    This view is served at /admin/dashboard/role-summary via an explicit route.
    Full role summary dashboard will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.dashboard_role_summary" context="dashboard" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Role Summary</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Overview of all roles, their permissions, and user assignments. Full role summary will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>