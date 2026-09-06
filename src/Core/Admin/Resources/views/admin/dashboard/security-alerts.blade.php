{{--
    Security Alerts page for the Dashboard context group.
    
    This view is served at /admin/dashboard/security-alerts via an explicit route.
    Full security alerts dashboard will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.dashboard_security_alerts" context="dashboard" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Security Alerts</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Monitor and respond to security-related alerts and notifications. Full security alerts will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>