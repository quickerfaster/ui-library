{{--
    System Settings page for the General Settings context group.
    
    This view is served at /system-settings via an explicit route.
    Full system settings configuration will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.system_settings" context="General Settings" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">System Settings</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Configure global system settings including application name, timezone, localization, and maintenance modes. Full system settings will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>