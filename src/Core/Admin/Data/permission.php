<?php

/**
 * Permission Data Config
 *
 * Minimal configuration for the Permissions management page.
 * Permissions are managed via Spatie Permission package.
 */
return [
    'model' => 'Spatie\Permission\Models\Permission',

    'fieldDefinitions' => [
        'name' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Permission Name',
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
            'default' => 'web',
            'filterable' => true,
        ],
    ],

    'fieldGroups' => [],

    'simpleActions' => ['show', 'edit', 'delete'],

    'controls' => [
        'addButton' => true,
    ],

    'crudType' => 'modal',

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
];