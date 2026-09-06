{{--
    Sessions page for the Security context group.
    
    This view resolves the /admin/sessions catch-all route.
    Full session management functionality will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.sessions" context="Security" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Sessions</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">View and manage active user sessions across the system. Full session management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>