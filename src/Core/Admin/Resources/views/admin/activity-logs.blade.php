{{--
    Activity Log page for the Audit context group.
    
    This view resolves the /admin/activity-logs catch-all route.
    A full datatable implementation requires a data config at
    Data/activity_log.php and a corresponding Livewire component.
--}}
<x-qf::navigation-layout configKey="admin.activity_log" context="audit" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Activity Log</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Track and review all user activities across the system. Full activity log functionality will be available in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>