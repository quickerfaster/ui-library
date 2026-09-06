{{--
    Login Restrictions page for the Security context group.
    
    This view is served at /admin/security/login-restrictions via an explicit route.
    Full login restriction configuration will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.security_login_restrictions" context="Security" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Login Restrictions</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Configure IP whitelisting, time-based access restrictions, and login attempt limits. Full login restriction management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>