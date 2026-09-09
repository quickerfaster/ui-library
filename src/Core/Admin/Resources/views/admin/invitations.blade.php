{{--
    Invitations page for the Users context group.
    
    This view resolves the /admin/invitations route.
    Displays the DataTable for managing user invitations
    with bulk invite capability.
--}}
<x-qf::navigation-layout configKey="admin.invitation" context="Users" moduleName="admin" :overrides="[]">
    <div class="d-flex justify-content-end mb-3">
        <button type="button"
            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center"
            onclick="Livewire.dispatch('openDrawer', {
                component: 'qf.bulk-invite',
                params: {},
                title: 'Bulk Invite'
            })">
            <i class="fas fa-users me-1"></i>
            Bulk Invite
        </button>
    </div>
    <livewire:qf.invitation-data-table configKey="admin.invitation" />
</x-qf::navigation-layout>