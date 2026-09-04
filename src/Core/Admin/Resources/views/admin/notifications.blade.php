{{--
    Admin page wrapper for the Notifications Index.
    Served at /admin/notifications via the catch-all route.
--}}
<x-qf::navigation-layout context="Notifications" moduleName="admin" :overrides=[]>
    <livewire:qf.notifications-index />
</x-qf::navigation-layout>