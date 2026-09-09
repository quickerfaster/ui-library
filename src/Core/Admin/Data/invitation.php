<?php

/**
 * Data Configuration: Admin Invitation
 *
 * Drives the DataTable and DataTableForm components for the Invitation entity
 * in the Admin module. Invitations are created via the DataTableForm and
 * processed by InvitationRecordListener (token generation, email dispatch).
 *
 * Config Key: admin.invitation
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    */
    'model' => \QuickerFaster\UILibrary\Models\Invitation::class,

    /*
    |--------------------------------------------------------------------------
    | Display Name
    |--------------------------------------------------------------------------
    */
    'label' => 'Invitation',
    'label_plural' => 'Invitations',

    /*
    |--------------------------------------------------------------------------
    | Field Definitions
    |--------------------------------------------------------------------------
    */
    'fieldDefinitions' => [
        'email' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'string',
            'label' => 'Email Address',
            'validation' => 'required|email|max:255',
            'filterable' => true,
            'searchable' => true,
        ],
        'status' => [
            'display' => 'inline',
            'fillable' => false,
            'field_type' => 'select',
            'label' => 'Status',
            'validation' => 'nullable|string|in:pending,accepted,expired,revoked',
            'options' => [
                'pending' => 'Pending',
                'accepted' => 'Accepted',
                'expired' => 'Expired',
                'revoked' => 'Revoked',
            ],
            'filterable' => true,
            'searchable' => true,
        ],
        'role' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'select',
            'label' => 'Role',
            'validation' => 'required|string|max:255',
            'filterable' => true,
            'searchable' => true,
            'options' => [
                'model' => \Spatie\Permission\Models\Role::class,
                'column' => 'name',
                'hintField' => '',
            ],
            'relationship' => [
                'model' => \Spatie\Permission\Models\Role::class,
                'type' => 'belongsTo',
                'display_field' => 'name',
                'dynamic_property' => 'roleRelation',
                'foreign_key' => 'role',
                'inlineAdd' => false,
            ],
        ],
        'message' => [
            'display' => 'inline',
            'fillable' => true,
            'field_type' => 'textarea',
            'label' => 'Personal Message',
            'validation' => 'nullable|string|max:1000',
        ],
        'token' => [
            'display' => 'inline',
            'fillable' => false,
            'field_type' => 'string',
            'label' => 'Token',
            'validation' => 'nullable|string|max:255',
        ],
        'expires_at' => [
            'display' => 'inline',
            'fillable' => false,
            'field_type' => 'datetimepicker',
            'label' => 'Expires At',
            'validation' => 'nullable|date',
            'filterable' => true,
        ],
        'accepted_at' => [
            'display' => 'inline',
            'fillable' => false,
            'field_type' => 'datetimepicker',
            'label' => 'Accepted At',
            'validation' => 'nullable|date',
        ],
        'revoked_at' => [
            'display' => 'inline',
            'fillable' => false,
            'field_type' => 'datetimepicker',
            'label' => 'Revoked At',
            'validation' => 'nullable|date',
        ],
        'created_by' => [
            'display' => 'inline',
            'fillable' => false,
            'field_type' => 'string',
            'label' => 'Created By',
            'validation' => 'nullable|integer|exists:users,id',
        ],
        'created_at' => [
            'display' => 'inline',
            'fillable' => false,
            'field_type' => 'datetimepicker',
            'label' => 'Sent At',
            'validation' => 'nullable|date',
            'filterable' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hidden Fields
    |--------------------------------------------------------------------------
    */
    'hiddenFields' => [
        'onTable' => [
            '0' => 'token',
            '1' => 'message',
            '2' => 'accepted_at',
            '3' => 'revoked_at',
            '4' => 'created_by',
            '5' => 'updated_at',
            '6' => 'invitable_type',
            '7' => 'invitable_id',
        ],
        'onNewForm' => [
            '0' => 'token',
            '1' => 'status',
            '2' => 'expires_at',
            '3' => 'accepted_at',
            '4' => 'revoked_at',
            '5' => 'created_by',
            '6' => 'created_at',
            '7' => 'updated_at',
            '8' => 'invitable_type',
            '9' => 'invitable_id',
        ],
        'onEditForm' => [
            '0' => 'token',
            '1' => 'status',
            '2' => 'expires_at',
            '3' => 'accepted_at',
            '4' => 'revoked_at',
            '5' => 'created_by',
            '6' => 'created_at',
            '7' => 'updated_at',
            '8' => 'invitable_type',
            '9' => 'invitable_id',
        ],
        'onQuery' => [
            '0' => 'token',
            '1' => 'message',
            '2' => 'invitable_type',
            '3' => 'invitable_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */
    'simpleActions' => [
        '0' => 'show',
        '1' => 'delete',
    ],

    /*
    |--------------------------------------------------------------------------
    | CRUD Type
    |--------------------------------------------------------------------------
    */
    'crudType' => 'drawers',
    'isTransaction' => false,
    'includeControllers' => false,
    'addRoutes' => false,
    'dispatchEvents' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Table Columns
    |--------------------------------------------------------------------------
    */
    'tableDefaultFields' => [
        '0' => 'email',
        '1' => 'status',
        '2' => 'role',
        '3' => 'created_at',
        '4' => 'expires_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Controls
    |--------------------------------------------------------------------------
    */
    'controls' => [
        'addButton' => true,
        'files' => [
            'export' => [
                '0' => 'csv',
                '1' => 'xls',
            ],
            'print' => false,
        ],
        'perPage' => [
            '0' => 10,
            '1' => 25,
            '2' => 50,
            '3' => 100,
        ],
        'search' => true,
        'showHideColumns' => true,
        'filterColumns' => true,
        'softDelete' => false,
        'restore' => false,
        'forceDelete' => false,
        'trashView' => false,
        'bulkActions' => [
            'export' => [
                '0' => 'csv',
                '1' => 'xls',
            ],
            'delete' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Groups (for the create/edit form)
    |--------------------------------------------------------------------------
    */
    'fieldGroups' => [
        'invitation_details' => [
            'title' => 'Invitation Details',
            'groupType' => 'admin',
            'icon' => 'fas fa-envelope',
            'fields' => [
                '0' => 'email',
                '1' => 'role',
                '2' => 'message',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Switch Views (list/card/table)
    |--------------------------------------------------------------------------
    */
    'switchViews' => [
        'default' => 'list',
        'list' => [
            'enabled' => true,
            'titleFields' => [
                '0' => 'email',
            ],
            'subtitleFields' => [
                '0' => 'role',
            ],
            'contentFields' => [
                '0' => 'status',
            ],
            'badgeField' => 'status',
            'badgeColors' => [
                'pending' => 'warning',
                'accepted' => 'success',
                'expired' => 'danger',
                'revoked' => 'secondary',
            ],
        ],
        'table' => [
            'enabled' => true,
        ],
        'card' => [
            'enabled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | More Actions (row-level context menu)
    |--------------------------------------------------------------------------
    */
    'moreActions' => [
        [
            'title' => 'Resend Invitation',
            'icon' => 'fas fa-paper-plane',
            'event' => 'resendInvitation',
            'permission' => 'view_invitation',
            'condition' => ['status' => 'pending'],
        ],
        [
            'title' => 'Revoke Invitation',
            'icon' => 'fas fa-ban',
            'event' => 'revokeInvitation',
            'permission' => 'view_invitation',
            'condition' => ['status' => 'pending'],
        ],
        [
            'title' => 'Copy Invitation Link',
            'icon' => 'fas fa-copy',
            'event' => 'copyInvitationLink',
            'permission' => 'view_invitation',
            'condition' => ['status' => 'pending'],
        ],
    ],
];