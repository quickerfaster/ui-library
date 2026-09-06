{{--
    User Preferences page for the Users context group.
    
    This view resolves the /admin/user-preferences catch-all route.
    Full user preferences management functionality will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.user_preferences" context="Users" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">User Preferences</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Configure default user preferences and system-wide user settings. Full user preferences management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>