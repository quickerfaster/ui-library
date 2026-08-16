{{--
    Admin page wrapper for the Workflow Definition list.

    This is a thin wrapper that provides the navigation-layout chrome
    (sidebar, top nav, breadcrumbs) and delegates all list UI to the
    Livewire component at:

        src/Resources/views/livewire/workflows/workflow-definition-list.blade.php

    This file exists as a catch-all route target so the admin module can serve
    the list under /admin/workflow-definitions with the standard admin layout.
--}}
<x-qf::navigation-layout configKey="admin.workflow_definitions" context="Workflows" moduleName="admin" :overrides=[]>
    <livewire:qf.data-table configKey="admin.workflow_definition" />
</x-qf::navigation-layout>
