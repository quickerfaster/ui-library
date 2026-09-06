{{--
    Authentication settings page for the Security context group.
    
    This view is served at /admin/security/authentication via an explicit route.
    Full authentication configuration will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.security_authentication" context="Security" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Authentication</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Configure authentication methods, SSO providers, and login settings. Full authentication management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>