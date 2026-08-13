<?php

/**
 * Data Configuration: Organization Branch
 *
 * Config Key: organization.branch
 */

return [
    'model' => \QuickerFaster\UILibrary\Core\Organization\Models\Branch::class,

    'label' => 'Branch',
    'label_plural' => 'Branches',

    /*
    |--------------------------------------------------------------------------
    | Field Definitions (Quick-HR compatible format)
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
        'name' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Branch Name',
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
        'address' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'textarea',
            'label' => 'Address',
            'validation' => 'nullable|string',
        ],
        'city' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'City',
            'validation' => 'nullable|string|max:100',
            'filterable' => true,
            'searchable' => true,
        ],
        'state' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'State',
            'validation' => 'nullable|string|max:100',
        ],
        'country' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Country',
            'validation' => 'nullable|string|max:100',
            'filterable' => true,
        ],
        'postal_code' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Postal Code',
            'validation' => 'nullable|string|max:20',
        ],
        'phone' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Phone',
            'validation' => 'nullable|string|max:50',
        ],
        'email' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Email',
            'validation' => 'nullable|email|max:255',
        ],
        'is_headquarters' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'select',
            'label' => 'Is Headquarters',
            'validation' => 'boolean',
            'options' => ['1' => 'Yes', '0' => 'No'],
            'filterable' => true,
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
            'label' => 'Branch Name',
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
        'city' => [
            'label' => 'City',
            'field' => 'city',
            'sortable' => true,
            'searchable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'country' => [
            'label' => 'Country',
            'field' => 'country',
            'sortable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'is_headquarters' => [
            'label' => 'Headquarters',
            'field' => 'is_headquarters',
            'sortable' => true,
            'type' => 'boolean',
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
        'name' => [
            'label' => 'Branch Name',
            'type' => 'text',
            'validation' => ['required', 'string', 'max:255'],
        ],
        'code' => [
            'label' => 'Code',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:50'],
        ],
        'address' => [
            'label' => 'Address',
            'type' => 'textarea',
            'validation' => ['nullable', 'string'],
        ],
        'city' => [
            'label' => 'City',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:100'],
        ],
        'state' => [
            'label' => 'State',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:100'],
        ],
        'country' => [
            'label' => 'Country',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:100'],
        ],
        'postal_code' => [
            'label' => 'Postal Code',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:20'],
        ],
        'phone' => [
            'label' => 'Phone',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:50'],
        ],
        'email' => [
            'label' => 'Email',
            'type' => 'email',
            'validation' => ['nullable', 'email', 'max:255'],
        ],
        'is_headquarters' => [
            'label' => 'Is Headquarters',
            'type' => 'checkbox',
            'validation' => ['boolean'],
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
        'fields' => ['id', 'company_id', 'name', 'code', 'address', 'city', 'state', 'country', 'postal_code', 'phone', 'email', 'is_headquarters', 'is_active', 'created_at', 'updated_at'],
    ],

    'default_sort' => [
        'field' => 'name',
        'direction' => 'asc',
    ],

    'per_page_options' => [10, 25, 50, 100],
    'default_per_page' => 25,
];