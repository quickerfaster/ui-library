{{--
    Recent Activity page for the Dashboard context group.
    
    This view is served at /admin/dashboard/recent-activity via an explicit route.
    Full recent activity feed will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.dashboard_recent_activity" context="dashboard" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Activity</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Real-time feed of recent actions and changes across the system. Full recent activity will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>