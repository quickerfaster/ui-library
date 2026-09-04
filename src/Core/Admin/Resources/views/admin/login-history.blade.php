{{--
    Login History page for the Audit context group.
    
    This view resolves the /admin/login-history catch-all route.
    A full datatable implementation requires a data config at
    Data/login_history.php and a corresponding Livewire component.
--}}
<x-qf::navigation-layout configKey="admin.login_history" context="audit" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Login History</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">View user login attempts, sessions, and authentication history. Full login history functionality will be available in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>