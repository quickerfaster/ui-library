<?php

/**
 * Data Configuration: System Setting
 *
 * Drives the DataTable, DataTableForm, and DataTableDetail components
 * for the SystemSetting entity in the System module.
 *
 * Config Key: system.system_setting
 *
 * @see docs/pre-phase-4-remediation-plan.md Section 2.5
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    | TODO: The SystemSetting model needs to be created or the consuming app
    | should provide its own model. For now, this references a library model
    | that may need to be created in a future phase.
    */
    'model' => \QuickerFaster\UILibrary\Models\SystemSetting::class,

    /*
    |--------------------------------------------------------------------------
    | Display Name
    |--------------------------------------------------------------------------
    */
    'label' => 'System Setting',
    'label_plural' => 'System Settings',

    /*
    |--------------------------------------------------------------------------
    | Field Definitions
    |--------------------------------------------------------------------------
    */
    'fieldDefinitions' => [
        'key' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Setting Key',
            'validation' => 'required|string|max:255',
            'filterable' => true,
            'searchable' => true,
        ],
        'value' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'textarea',
            'label' => 'Value',
            'validation' => 'nullable|string',
            'searchable' => true,
        ],
        'type' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'select',
            'label' => 'Type',
            'validation' => 'required|string',
            'options' => [
                'string' => 'String',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'json' => 'JSON',
            ],
            'filterable' => true,
        ],
        'description' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'textarea',
            'label' => 'Description',
            'validation' => 'nullable|string|max:500',
        ],
        'created_at' => [
            'display' => 'inline',
            'fillable' => false,
            'field_type' => 'datetimepicker',
            'label' => 'Created',
            'validation' => 'nullable|date',
        ],
        'updated_at' => [
            'display' => 'inline',
            'fillable' => false,
            'field_type' => 'datetimepicker',
            'label' => 'Last Updated',
            'validation' => 'nullable|date',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */
    'columns' => [
        'id' => [
            'label' => 'ID',
            'field' => 'id',
            'sortable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'key' => [
            'label' => 'Key',
            'field' => 'key',
            'sortable' => true,
            'searchable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'value' => [
            'label' => 'Value',
            'field' => 'value',
            'sortable' => false,
            'searchable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'type' => [
            'label' => 'Type',
            'field' => 'type',
            'sortable' => true,
            'type' => 'badge',
            'visible' => true,
        ],
        'updated_at' => [
            'label' => 'Last Updated',
            'field' => 'updated_at',
            'sortable' => true,
            'type' => 'date',
            'visible' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */
    'fields' => [
        'key' => [
            'label' => 'Setting Key',
            'type' => 'text',
            'validation' => ['required', 'string', 'max:255'],
        ],
        'value' => [
            'label' => 'Value',
            'type' => 'textarea',
            'validation' => ['nullable', 'string'],
        ],
        'type' => [
            'label' => 'Type',
            'type' => 'select',
            'options' => [
                'string' => 'String',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'json' => 'JSON',
            ],
            'default' => 'string',
            'validation' => ['required', 'string'],
        ],
        'description' => [
            'label' => 'Description',
            'type' => 'textarea',
            'validation' => ['nullable', 'string', 'max:500'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */
    'filters' => [
        // TODO: Add filter definitions (e.g., type filter)
    ],

    /*
    |--------------------------------------------------------------------------
    | Controls
    |--------------------------------------------------------------------------
    */
    'controls' => [
        'create' => true,
        'edit' => true,
        'delete' => true,
        'view' => true,
        'export' => true,
        'print' => true,
        'softDelete' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Detail View
    |--------------------------------------------------------------------------
    */
    'detail' => [
        'fields' => ['id', 'key', 'value', 'type', 'description', 'created_at', 'updated_at'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Sort
    |--------------------------------------------------------------------------
    */
    'default_sort' => [
        'field' => 'key',
        'direction' => 'asc',
    ],

    /*
    |--------------------------------------------------------------------------
    | Per Page Options
    |--------------------------------------------------------------------------
    */
    'per_page_options' => [5, 10, 25, 50],
    'default_per_page' => 10,
];