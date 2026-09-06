{{--
    User Statistics page for the Dashboard context group.
    
    This view is served at /admin/dashboard/user-statistics via an explicit route.
    Full user statistics dashboard will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.dashboard_user_statistics" context="dashboard" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">User Statistics</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">View detailed statistics about user activity, growth, and engagement. Full user statistics will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>