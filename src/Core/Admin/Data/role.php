<?php

/**
 * Data Configuration: Admin Role
 *
 * Drives the DataTable, DataTableForm, and DataTableDetail components
 * for the Role entity in the Admin module.
 *
 * Config Key: admin.role
 *
 * NOTE: The Role model is provided by Spatie Permission (Spatie\Permission\Models\Role).
 * This config provides sensible defaults for role management.
 *
 * @see docs/pre-phase-4-remediation-plan.md Section 2.4
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    */
    'model' => \Spatie\Permission\Models\Role::class,

    /*
    |--------------------------------------------------------------------------
    | Display Name
    |--------------------------------------------------------------------------
    */
    'label' => 'Role',
    'label_plural' => 'Roles',

    /*
    |--------------------------------------------------------------------------
    | Field Definitions
    |--------------------------------------------------------------------------
    */
    'fieldDefinitions' => [
        'name' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Role Name',
            'validation' => 'required|string|max:255',
            'filterable' => true,
            'searchable' => true,
        ],
        'guard_name' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'select',
            'label' => 'Guard',
            'validation' => 'required|string|in:web,api',
            'options' => [
                'web' => 'Web',
                'api' => 'API',
            ],
            'filterable' => true,
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
            'label' => 'Updated',
            'validation' => 'nullable|date',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Columns (backward compatibility)
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
        'name' => [
            'label' => 'Name',
            'field' => 'name',
            'sortable' => true,
            'searchable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'guard_name' => [
            'label' => 'Guard',
            'field' => 'guard_name',
            'sortable' => true,
            'type' => 'badge',
            'visible' => true,
        ],
        'created_at' => [
            'label' => 'Created',
            'field' => 'created_at',
            'sortable' => true,
            'type' => 'date',
            'visible' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Fields (backward compatibility)
    |--------------------------------------------------------------------------
    */
    'fields' => [
        'name' => [
            'label' => 'Role Name',
            'type' => 'text',
            'validation' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ],
        'guard_name' => [
            'label' => 'Guard Name',
            'type' => 'select',
            'options' => ['web' => 'Web', 'api' => 'API'],
            'default' => 'web',
            'validation' => ['required', 'string'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hidden Fields
    |--------------------------------------------------------------------------
    */
    'hiddenFields' => [
        'onTable' => [],
        'onNewForm' => [],
        'onEditForm' => [],
        'onQuery' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */
    'filters' => [
        // TODO: Add filter definitions (e.g., guard_name filter)
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
        'fields' => ['id', 'name', 'guard_name', 'created_at', 'updated_at'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Sort
    |--------------------------------------------------------------------------
    */
    'default_sort' => [
        'field' => 'id',
        'direction' => 'asc',
    ],

    /*
    |--------------------------------------------------------------------------
    | Per Page Options
    |--------------------------------------------------------------------------
    */
    'per_page_options' => [5, 10, 25, 50],
    'default_per_page' => 10,

    /*
    |--------------------------------------------------------------------------
    | Simple Actions
    |--------------------------------------------------------------------------
    */
    'simpleActions' => [
        '0' => 'show',
        '1' => 'edit',
        '2' => 'delete',
    ],

    'isTransaction' => false,
    'crudType' => 'drawers',
    'includeControllers' => false,
    'tableDefaultFields' => [
        '0' => 'id',
        '1' => 'name',
        '2' => 'guard_name',
        '3' => 'created_at',
    ],
    'addRoutes' => false,
    'dispatchEvents' => false,
    'detailComponent' => '',
    'fieldGroups' => [],
    'moreActions' => [],
    'switchViews' => [
        'default' => 'list',
        'table' => ['enabled' => true],
        'list' => [
            'enabled' => true,
            'titleFields' => ['name'],
            'subtitleFields' => ['guard_name'],
            'badgeField' => 'guard_name',
            'badgeColors' => [
                'web' => 'primary',
                'api' => 'info',
            ],
        ],
    ],
    'relations' => [],
    'report' => [],
];