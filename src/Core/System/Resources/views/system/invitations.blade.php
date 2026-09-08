{{--
    Invitations audit page for the Accounts context group.

    Read-only view showing all invitations across companies.
    Uses the same admin.invitation DataTable config, but in a
    system-level read-only context.
--}}
<x-qf::navigation-layout configKey="admin.invitation" context="accounts" moduleName="system" :overrides="[]">
    <livewire:qf.data-table configKey="admin.invitation" />
</x-qf::navigation-layout>