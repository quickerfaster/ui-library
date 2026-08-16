<?php

return [
  'model' => config('ui-library.user.model', config('auth.providers.users.model', 'App\\Models\\User')),
  'fieldDefinitions' => [
    'name' => [
      'display' => 'inline',
      'fillable' => true,
      'field_type' => 'string',
      'label' => 'Full Name',
      'validation' => 'required|string|max:255',
      'filterable' => true,
      'searchable' => true,
      'wizard' => [
        'user_onboarding' => true,
      ],
    ],
    'email' => [
      'display' => 'inline',
      'fillable' => true,
      'field_type' => 'string',
      'label' => 'Email Address',
      'validation' => 'required|email|max:255|unique:users,email',
      'filterable' => true,
      'searchable' => true,
      'wizard' => [
        'user_onboarding' => true,
      ],
    ],
    'status' => [
      'display' => 'inline',
      'fillable' => true,
      'field_type' => 'select',
      'label' => 'Status',
      'validation' => 'required',
      'options' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'invited' => 'Invited',
      ],
      'filterable' => true,
      'searchable' => true,
    ],
    'company_id' => [
      'display' => 'inline',
      'fillable' => true,
      'field_type' => 'select',
      'label' => 'Company',
      'validation' => 'required|integer|exists:companies,id',
      'filterable' => true,
      'searchable' => true,
      'relationship' => [
        'model' => 'QuickerFaster\UILibrary\Core\Organization\Models\Company',
        'type' => 'belongsTo',
        'display_field' => 'name',
        'dynamic_property' => 'company',
        'foreign_key' => 'company_id',
        'inlineAdd' => false,
      ],
      'options' => [
        'model' => 'QuickerFaster\UILibrary\Core\Organization\Models\Company',
        'column' => 'name',
        'hintField' => '',
      ],
    ],
    'password' => [
      'display' => 'inline',
      'fillable' => true,
      'field_type' => 'password',
      'label' => 'Password',
      'validation' => 'required|string|min:8|confirmed',
      'wizard' => [
        'user_onboarding' => true,
      ],
    ],
    'password_confirmation' => [
      'display' => 'inline',
      'fillable' => false,
      'field_type' => 'password',
      'label' => 'Confirm Password',
      'validation' => 'required_with:password|same:password',
    ],
    'email_verified_at' => [
      'display' => 'inline',
      'fillable' => false,
      'field_type' => 'datetimepicker',
      'label' => 'Email Verified At',
      'validation' => 'nullable|date',
    ],
    'remember_token' => [
      'display' => 'inline',
      'fillable' => false,
      'field_type' => 'string',
      'label' => 'Remember Token',
      'validation' => 'nullable|string|max:255',
    ],
  ],
  'detailComponent' => '',
  'hiddenFields' => [
    'onTable' => [
      '0' => 'password',
      '1' => 'password_confirmation',
      '2' => 'remember_token',
      '3' => 'email_verified_at',
      '4' => 'created_at',
      '5' => 'updated_at',
      '6' => 'deleted_at',
      '7' => 'company_id',
    ],
    'onNewForm' => [
      '0' => 'email_verified_at',
      '1' => 'remember_token',
      '2' => 'created_at',
      '3' => 'updated_at',
      '4' => 'deleted_at',
      '5' => 'company_id',
    ],
    'onEditForm' => [
      '0' => 'remember_token',
      '1' => 'email_verified_at',
      '2' => 'deleted_at',
      '3' => 'company_id',
    ],
    'onQuery' => [
      '0' => 'password_confirmation',
      '1' => 'remember_token',
      '2' => 'deleted_at',
    ],
  ],
  'simpleActions' => [
    '0' => 'show',
    '1' => 'edit',
    '2' => 'delete',
  ],
  'isTransaction' => false,
  'crudType' => 'drawers',
  'includeControllers' => false,
  'tableDefaultFields' => [
    '0' => 'company_id',
    '1' => 'name',
    '2' => 'email',
    '3' => 'status',
  ],
  'addRoutes' => false,
  'dispatchEvents' => false,
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
    'softDelete' => true,
    'restore' => true,
    'forceDelete' => true,
    'trashView' => true,
    'bulkActions' => [
      'export' => [
        '0' => 'csv',
        '1' => 'xls',
      ],
      'delete' => true,
      'restore' => true,
      'forceDelete' => true,
    ],
  ],
  'fieldGroups' => [
    'company' => [
      'title' => 'Company',
      'groupType' => 'admin',
      'icon' => 'fas fa-building',
      'fields' => [
        '0' => 'company_id',
      ],
    ],
    'identity' => [
      'title' => 'Identity',
      'groupType' => 'admin',
      'icon' => 'fas fa-id-card',
      'fields' => [
        '0' => 'name',
        '1' => 'email',
        '2' => 'status',
      ],
    ],
    'authentication' => [
      'title' => 'Authentication',
      'groupType' => 'admin',
      'icon' => 'fas fa-lock',
      'fields' => [
        '0' => 'password',
        '1' => 'password_confirmation',
      ],
    ],
  ],
  'moreActions' => [],
  'switchViews' => [
    'default' => 'list',
    'list' => [
      'enabled' => true,
      'titleFields' => [
        '0' => 'name',
      ],
      'subtitleFields' => [
        '0' => 'email',
      ],
      'contentFields' => [
        '0' => 'status',
      ],
      'badgeField' => 'status',
      'badgeColors' => [
        'active' => 'success',
        'inactive' => 'secondary',
        'invited' => 'warning',
      ],
    ],
    'table' => [
      'enabled' => true,
    ],
    'card' => [
      'enabled' => false,
    ],
  ],
  'relations' => [
    'roles' => [
      'type' => 'morphToMany',
      'model' => 'Spatie\Permission\Models\Role',
      'foreignKey' => 'model_id',
      'localKey' => '',
      'pivotTable' => 'model_has_roles',
      'foreignPivotKey' => '',
      'relatedPivotKey' => 'role_id',
      'morphType' => 'model_type',
      'morphName' => 'roles',
      'displayField' => 'name',
    ],
    // NOTE: 'profile' relation removed — it is a consuming-app concern.
    // Consuming apps that need a 'profile' relation on App\Models\User should
    // publish this config and add their own relation definition.
  ],
  'report' => [],
];
