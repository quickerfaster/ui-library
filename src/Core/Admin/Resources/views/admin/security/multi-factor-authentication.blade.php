{{--
    Multi-Factor Authentication page for the Security context group.
    
    This view is served at /admin/security/multi-factor-authentication via an explicit route.
    Full MFA configuration will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.security_multi_factor_authentication" context="Security" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Multi-Factor Authentication</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Configure multi-factor authentication requirements, methods, and enforcement policies. Full MFA management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>