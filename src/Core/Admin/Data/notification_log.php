<?php

return [
    'model' => \QuickerFaster\UILibrary\Models\NotificationLog::class,

    'fieldDefinitions' => [
        'notifiable_type' => [
            'field_type' => 'string',
            'label' => 'Notifiable Type',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
        ],
        'notifiable_id' => [
            'field_type' => 'string',
            'label' => 'Notifiable ID',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
        ],
        'type' => [
            'field_type' => 'select',
            'label' => 'Type',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
            'options' => [
                'workflow_submitted' => 'Workflow Submitted',
                'workflow_approved' => 'Workflow Approved',
                'workflow_rejected' => 'Workflow Rejected',
                'workflow_recalled' => 'Workflow Recalled',
                'document_generated' => 'Document Generated',
                'report_ready' => 'Report Ready',
                'workflow_stage_changed' => 'Workflow Stage Changed',
            ],
        ],
        'channel' => [
            'field_type' => 'string',
            'label' => 'Channel',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
        ],
        'status' => [
            'field_type' => 'select',
            'label' => 'Status',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
            'options' => [
                'sent' => 'Sent',
                'failed' => 'Failed',
                'pending' => 'Pending',
            ],
        ],
        'error_message' => [
            'field_type' => 'text',
            'label' => 'Error Message',
            'filterable' => true,
            'searchable' => true,
        ],
        'created_at' => [
            'field_type' => 'datetime',
            'label' => 'Created',
            'sortable' => true,
        ],
    ],

    'hiddenFields' => [
        'onTable' => ['error_message'],
    ],

    'simpleActions' => ['show'],

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
            'titleFields' => ['type'],
            'subtitleFields' => ['channel', 'status'],
            'badgeField' => 'status',
            'badgeColors' => [
                'sent' => 'success',
                'failed' => 'danger',
                'pending' => 'warning',
            ],
        ],
    ],

    'tableDefaultFields' => [
        'notifiable_type',
        'notifiable_id',
        'type',
        'channel',
        'status',
        'created_at',
    ],
];