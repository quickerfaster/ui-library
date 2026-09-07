<x-qf::navigation-layout
    configKey="admin.user_company_assignments"
    context="Users"
    moduleName="admin"
    :overrides="[]">
    
    @livewire('user-company-assignment', ['user' => request('user')])
</x-qf::navigation-layout>