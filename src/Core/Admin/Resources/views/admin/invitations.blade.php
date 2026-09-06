{{--
    Invitations page for the Users context group.
    
    This view resolves the /admin/invitations catch-all route.
    Full invitation management functionality will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.invitations" context="Users" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Invitations</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Manage user invitations and track invitation status. Full invitation management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>