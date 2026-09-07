{{--
    Company Assignments page for the Users context group.
    
    This view resolves the /admin/user-company-assignments catch-all route.
    Assign multiple companies to users. This feature requires the consuming app to implement
    the UserCompanyAssignment Livewire component and the company_user pivot table.
--}}
<x-qf::navigation-layout configKey="admin.user_company_assignments" context="Users" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Company Assignments</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Assign multiple companies to users. This feature requires the consuming app to implement 
                the <code>UserCompanyAssignment</code> Livewire component and the <code>company_user</code> pivot table.
            </p>
            <p class="text-muted">
                See <code>plans/consuming-app-multi-company-implementation.md</code> for implementation details.
            </p>
        </div>
    </div>
</x-qf::navigation-layout>