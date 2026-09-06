{{--
    API Tokens page for the Security context group.
    
    This view is served at /admin/security/api-tokens via an explicit route.
    Full API token management will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.security_api_tokens" context="Security" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">API Tokens</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Manage API tokens for programmatic access to the system. Full API token management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>