<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

/**
 * WorkflowDefinitionDetail — extends the default DataTableDetail to also
 * render the workflow definition's approval steps (initiators, reviewers,
 * authorizers) in the detail drawer.
 */
class WorkflowDefinitionDetail extends DataTableDetail
{
    public function render()
    {
        // Eager-load steps so the view can render them
        if ($this->record && method_exists($this->record, 'steps')) {
            $this->record->load('steps');
        }

        return view('qf::livewire.data-tables.partials.workflow-definition-detail', [
            'record' => $this->record,
            'fieldDefinitions' => $this->fieldDefinitions,
            'fieldGroups' => $this->fieldGroups,
            'hiddenFields' => $this->hiddenFields,
            'configKey' => $this->configKey,
            'crudType' => $this->crudType,
        ]);
    }
}