<?php

/**
 * Data Configuration: Organization Company
 *
 * Drives the DataTable, DataTableForm, and DataTableDetail components
 * for the Company entity in the Organization module.
 *
 * Config Key: organization.company
 */

return [
    'model' => \QuickerFaster\UILibrary\Core\Organization\Models\Company::class,

    'label' => 'Company',
    'label_plural' => 'Companies',

    /*
    |--------------------------------------------------------------------------
    | Field Definitions (Quick-HR compatible format)
    |--------------------------------------------------------------------------
    */
    'fieldDefinitions' => [
        'name' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Company Name',
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
        'email' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Email',
            'validation' => 'nullable|email|max:255',
            'filterable' => true,
            'searchable' => true,
        ],
        'phone' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Phone',
            'validation' => 'nullable|string|max:50',
            'filterable' => true,
        ],
        'website' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Website',
            'validation' => 'nullable|url|max:255',
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
            'searchable' => true,
        ],
        'postal_code' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Postal Code',
            'validation' => 'nullable|string|max:20',
        ],
        'tax_id' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Tax ID',
            'validation' => 'nullable|string|max:100',
        ],
        'registration_number' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Registration Number',
            'validation' => 'nullable|string|max:100',
        ],
        'currency' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'select',
            'label' => 'Currency',
            'validation' => 'nullable|string|max:3',
            'options' => ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'NGN' => 'NGN'],
        ],
        'timezone' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Timezone',
            'validation' => 'nullable|string|max:50',
        ],
        'date_format' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Date Format',
            'validation' => 'nullable|string|max:20',
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
            'label' => 'Company Name',
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
        'email' => [
            'label' => 'Email',
            'field' => 'email',
            'sortable' => true,
            'searchable' => true,
            'type' => 'text',
            'visible' => true,
        ],
        'phone' => [
            'label' => 'Phone',
            'field' => 'phone',
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
            'searchable' => true,
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
        'name' => [
            'label' => 'Company Name',
            'type' => 'text',
            'validation' => ['required', 'string', 'max:255'],
        ],
        'code' => [
            'label' => 'Code',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:50'],
        ],
        'email' => [
            'label' => 'Email',
            'type' => 'email',
            'validation' => ['nullable', 'email', 'max:255'],
        ],
        'phone' => [
            'label' => 'Phone',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:50'],
        ],
        'website' => [
            'label' => 'Website',
            'type' => 'url',
            'validation' => ['nullable', 'url', 'max:255'],
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
        'tax_id' => [
            'label' => 'Tax ID',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:100'],
        ],
        'registration_number' => [
            'label' => 'Registration Number',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:100'],
        ],
        'currency' => [
            'label' => 'Currency',
            'type' => 'select',
            'validation' => ['nullable', 'string', 'max:3'],
            'options' => ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'NGN' => 'NGN'],
        ],
        'timezone' => [
            'label' => 'Timezone',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:50'],
        ],
        'date_format' => [
            'label' => 'Date Format',
            'type' => 'text',
            'validation' => ['nullable', 'string', 'max:20'],
        ],
        'is_active' => [
            'label' => 'Active',
            'type' => 'checkbox',
            'validation' => ['boolean'],
        ],
    ],

    'filters' => [
        'is_active' => [
            'label' => 'Status',
            'type' => 'select',
            'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive'],
        ],
        'country' => [
            'label' => 'Country',
            'type' => 'text',
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
        'fields' => ['id', 'name', 'code', 'email', 'phone', 'website', 'address', 'city', 'state', 'country', 'postal_code', 'tax_id', 'registration_number', 'currency', 'timezone', 'date_format', 'is_active', 'created_at', 'updated_at'],
    ],

    'default_sort' => [
        'field' => 'name',
        'direction' => 'asc',
    ],

    'per_page_options' => [10, 25, 50, 100],
    'default_per_page' => 25,
];