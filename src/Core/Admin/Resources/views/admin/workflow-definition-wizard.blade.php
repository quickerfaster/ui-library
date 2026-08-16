{{--
    Admin page wrapper for the Workflow Definition Wizard.

    This is a thin wrapper that provides the navigation-layout chrome
    (sidebar, top nav, breadcrumbs) and delegates all wizard UI to the
    Livewire component at:

        src/Resources/views/livewire/workflows/workflow-definition-wizard.blade.php

    The real wizard UX — step tracker, form fields, reviewer chain builder,
    summary, and save logic — lives entirely in the Livewire component view
    and its backing WorkflowDefinitionWizard class. This file exists solely
    as a catch-all route target so the admin module can serve the wizard
    under /admin/workflow-definition-wizard with the standard admin layout.
--}}
<x-qf::navigation-layout configKey="admin.wizards.workflow_definition" context="Workflows" moduleName="admin" :overrides=[]>
    <div class="row">
        <div class="col-12">
            <livewire:qf.workflow-definition-wizard />
        </div>
    </div>
</x-qf::navigation-layout>