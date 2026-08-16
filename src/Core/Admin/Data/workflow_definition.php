<?php

return [
    'model' => \QuickerFaster\UILibrary\Models\WorkflowDefinition::class,

    'fieldDefinitions' => [
        'name' => [
            'field_type' => 'string',
            'label' => 'Name',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
        ],
        'key' => [
            'field_type' => 'string',
            'label' => 'Key',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
        ],
        'entity_type' => [
            'field_type' => 'string',
            'label' => 'Entity Type',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
        ],
        'is_active' => [
            'field_type' => 'checkbox',
            'label' => 'Status',
            'filterable' => true,
            'options' => [1 => 'Active', 0 => 'Inactive'],
        ],
        'description' => [
            'field_type' => 'textarea',
            'label' => 'Description',
        ],
        'notifications' => [
            'field_type' => 'string',
            'label' => 'Notifications',
        ],
        'created_at' => [
            'field_type' => 'datetime',
            'label' => 'Created',
            'sortable' => true,
        ],
        'updated_at' => [
            'field_type' => 'datetime',
            'label' => 'Updated',
            'sortable' => true,
        ],
    ],

    'hiddenFields' => [
        'onTable' => ['description', 'notifications', 'created_at', 'updated_at'],
    ],

    'simpleActions' => [],

    'moreActions' => [
        [
            'title' => 'Edit in Wizard',
            'icon' => 'fas fa-pen',
            'url' => '/admin/workflow-definition-wizard?definitionId={id}',
            'action' => 'edit',
        ],
    ],

    'controls' => [
        'addButton' => false,
        'search' => true,
        'showHideColumns' => true,
        'filterColumns' => true,
        'perPage' => [10, 25, 50],
        'softDelete' => false,
    ],

    'switchViews' => [
        'default' => 'table',
        'table' => ['enabled' => true],
        'list' => [
            'enabled' => true,
            'titleFields' => ['name'],
            'subtitleFields' => ['entity_type'],
            'badgeField' => 'is_active',
            'badgeColors' => [1 => 'success', 0 => 'secondary'],
        ],
    ],

    'tableDefaultFields' => ['name', 'key', 'entity_type', 'is_active'],
];