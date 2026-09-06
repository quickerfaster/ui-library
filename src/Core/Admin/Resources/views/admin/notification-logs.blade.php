{{--
    Admin page wrapper for Notification Delivery Logs.
    Served at /admin/notification-logs via the catch-all route.
--}}
<x-qf::navigation-layout configKey="admin.notification_log" context="Notifications" moduleName="admin" :overrides=[]>
    <livewire:qf.data-table configKey="admin.notification_log" />
</x-qf::navigation-layout>