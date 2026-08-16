<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Workflows;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\WorkflowDefinition;

/**
 * Workflow definition list page.
 *
 * Lists all WorkflowDefinition rows in a Bootstrap table with columns for
 * name, key, entity type, status, step count, and an edit action that links
 * back into the workflow definition wizard via `?definitionId=N`.
 *
 * @deprecated Replaced by the generic DataTable component (`qf.data-table`)
 *             with config key `admin.workflow_definition`. The DataTable
 *             provides search, sort, pagination, filtering, column management,
 *             export, and permission gating — all missing from this component.
 *             See `src/Core/Admin/Data/workflow_definition.php` for the config.
 */
class WorkflowDefinitionList extends Component
{
    public function render()
    {
        $definitions = WorkflowDefinition::query()
            ->withCount('steps')
            ->orderBy('name')
            ->get();

        return view('qf::livewire.workflows.workflow-definition-list', [
            'definitions' => $definitions,
        ]);
    }
}
