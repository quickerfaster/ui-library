{{--
    Audit Exports page for the Audit context group.
    
    This view resolves the /admin/audit-exports catch-all route.
    A full datatable implementation requires a data config at
    Data/audit_export.php and a corresponding Livewire component.
--}}
<x-qf::navigation-layout configKey="admin.audit_export" context="audit" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Audit Exports</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Export audit logs, activity reports, and compliance data. Full export functionality will be available in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>