{{--
    Invitations page for the Users context group.
    
    This view resolves the /admin/invitations route.
    Displays the DataTable for managing user invitations.
--}}
<x-qf::navigation-layout configKey="admin.invitation" context="Users" moduleName="admin" :overrides="[]">
    <livewire:qf.data-table configKey="admin.invitation" />
</x-qf::navigation-layout>