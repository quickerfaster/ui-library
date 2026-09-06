{{--
    System Events page for the Audit context group.
    
    This view resolves the /admin/system-events catch-all route.
    A full datatable implementation requires a data config at
    Data/system_event.php and a corresponding Livewire component.
--}}
<x-qf::navigation-layout configKey="admin.system_event" context="audit" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">System Events</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Monitor system-level events, errors, and operational logs. Full system events functionality will be available in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>