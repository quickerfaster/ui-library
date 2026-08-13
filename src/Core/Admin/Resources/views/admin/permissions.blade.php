<x-qf::navigation-layout configKey="admin.permission" context="Users & Permissions" moduleName="admin" :overrides=[]>
    {{-- Permissions are managed via Spatie Permission. --}}
    {{-- This view provides access control management for roles and users. --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Permissions</h5>
        </div>
        <div class="card-body">
            <p>Manage role-based and user-based permissions. Use the Access Control panel to assign permissions to roles and users.</p>
            <livewire:qf.access-control-manager />
        </div>
    </div>
</x-qf::navigation-layout>