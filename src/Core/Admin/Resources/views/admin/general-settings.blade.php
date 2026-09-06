{{--
    General Settings page for the Settings context group.
    
    This view resolves the /admin/general-settings catch-all route.
    Full general settings management functionality will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.general-settings" context="Settings" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">General Settings</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Configure general application settings and preferences. Full settings management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>