<?php

/**
 * Data Configuration: Organization Department
 *
 * Config Key: organization.department
 */

return [
    'model' => \QuickerFaster\UILibrary\Core\Organization\Models\Department::class,

    'label' => 'Department',
    'label_plural' => 'Departments',

    /*
    |--------------------------------------------------------------------------
    | Field Definitions
    |--------------------------------------------------------------------------
    */
    'fieldDefinitions' => [
        'company_id' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'select',
            'label' => 'Company',
            'validation' => 'required|exists:companies,id',
            'filterable' => true,
        ],
        'branch_id' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'select',
            'label' => 'Branch',
            'validation' => 'nullable|exists:branches,id',
            'filterable' => true,
        ],
        'parent_department_id' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'select',
            'label' => 'Parent Department',
            'validation' => 'nullable|exists:departments,id',
        ],
        'name' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Department Name',
            'validation' => 'required|string|max:255',
            'filterable' => true,
            'searchable' => true,
        ],
        'code' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Code',
            'validation' => 'nullable|string|max:50',
            'filterable' => true,
            'searchable' => true,
        ],
        'description' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'textarea',
            'label' => 'Description',
            'validation' => 'nullable|string',
        ],
        'is_active' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'select',
            'label' => 'Active',
            'validation' => 'boolean',
            'options' => ['1' => 'Active', '0' => 'Inactive'],
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

    'columns' => [
        'id' => [
            'label' => 'ID',
            'field' => 'id',
            'sortable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'name' => [
            'label' => 'Department Name',
            'field' => 'name',
            'sortable' => true,
            'searchable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'code' => [
            'label' => 'Code',
            'field' => 'code',
            'sortable' => true,
            'searchable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'company_id' => [
            'label' => 'Company',
            'field' => 'company_id',
            'sortable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'branch_id' => [
            'label' => 'Branch',
            'field' => 'branch_id',
            'sortable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'is_active' => [
            'label' => 'Active',
            'field' => 'is_active',
            'sortable' => true,
            'type' => 'boolean',
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

    'fields' => [
        'company_id' => [
            'label' => 'Company',
            'type' => 'select',
            'validation' => ['required', 'exists:companies,id'],
        ],
        'branch_id' => [
            'label' => 'Branch',
            'type' => 'select',
            'validation' => ['nullable', 'exists:branches,id'],
        ],
        'parent_department_id' => [
            'label' => 'Parent Department',
            'type' => 'select',
            'validation' => ['nullable', 'exists:departments,id'],
        ],
        'name' => [
            'label' => 'Department Name',
            'type' => 'text',
            'validation' => ['required', 'string', 'max:255'],
        ],
        'code' => [
            'label' => 'Code',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:50'],
        ],
        'description' => [
            'label' => 'Description',
            'type' => 'textarea',
            'validation' => ['nullable', 'string'],
        ],
        'is_active' => [
            'label' => 'Active',
            'type' => 'checkbox',
            'validation' => ['boolean'],
        ],
    ],

    'filters' => [
        'company_id' => [
            'label' => 'Company',
            'type' => 'select',
        ],
        'branch_id' => [
            'label' => 'Branch',
            'type' => 'select',
        ],
        'is_active' => [
            'label' => 'Status',
            'type' => 'select',
            'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive'],
        ],
    ],

    'controls' => [
        'create' => true,
        'edit' => true,
        'delete' => true,
        'view' => true,
        'export' => true,
        'import' => true,
        'print' => true,
        'softDelete' => true,
    ],

    'detail' => [
        'fields' => ['id', 'company_id', 'branch_id', 'parent_department_id', 'name', 'code', 'description', 'is_active', 'created_at', 'updated_at'],
    ],

    'default_sort' => [
        'field' => 'name',
        'direction' => 'asc',
    ],

    'per_page_options' => [10, 25, 50, 100],
    'default_per_page' => 25,
];