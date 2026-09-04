{{--
    Password Policies page for the Security context group.
    
    This view is served at /admin/security/password-policies via an explicit route.
    Full password policy configuration will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.security_password_policies" context="Security" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Password Policies</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Define and enforce password complexity requirements, expiration, and history rules. Full password policy management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>