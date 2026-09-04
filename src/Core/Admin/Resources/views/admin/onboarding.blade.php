{{--
    Onboarding page for the Settings context group.
    
    This view resolves the /admin/onboarding catch-all route.
    Full onboarding management functionality will be implemented in a future update.
--}}
<x-qf::navigation-layout configKey="admin.onboarding" context="Settings" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Onboarding</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Manage onboarding flows and setup checklists for new users. Full onboarding management will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>