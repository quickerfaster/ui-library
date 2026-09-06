<?php

return array (
  'title' => 'Admin Dashboard',
  'description' => 'Monitor user accounts, roles, permissions, and access control metrics',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Total Users',
      'size' => 'col-12',
      'model' => 'App\\Models\\User',
      'icon' => 'fas fa-users',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Active Users',
      'size' => 'col-12',
      'model' => 'App\\Models\\User',
      'icon' => 'fas fa-user-check',
      'aggregate' => 'count',
      'conditions' =>
      array (
        0 =>
        array (
          0 => 'status',
          1 => '=',
          2 => 'active',
        ),
      ),
      'width' => 3,
    ),
    2 =>
    array (
      'type' => 'stat',
      'title' => 'Total Roles',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Role',
      'icon' => 'fas fa-user-tag',
      'aggregate' => 'count',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'stat',
      'title' => 'Total Permissions',
      'size' => 'col-12',
      'model' => 'Spatie\\Permission\\Models\\Permission',
      'icon' => 'fas fa-key',
      'aggregate' => 'count',
      'width' => 3,
    ),
    4 =>
    array (
      'type' => 'chart',
      'title' => 'Users by Status',
      'size' => 'col-12',
      'model' => 'App\\Models\\User',
      'group_by' => 'status',
      'chart_type' => 'pie',
      'description' => 'Distribution of user account statuses',
      'aggregate' => 'count',
      'width' => 4,
    ),
    5 =>
    array (
      'type' => 'chart',
      'title' => 'Roles by Guard',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Role',
      'group_by' => 'guard_name',
      'chart_type' => 'bar',
      'description' => 'Web vs. API roles',
      'aggregate' => 'count',
      'width' => 4,
    ),
    6 =>
    array (
      'type' => 'chart',
      'title' => 'Permissions by Guard',
      'size' => 'col-12',
      'model' => 'Spatie\\Permission\\Models\\Permission',
      'group_by' => 'guard_name',
      'chart_type' => 'bar',
      'description' => 'Web vs. API permissions',
      'aggregate' => 'count',
      'width' => 4,
    ),
    7 =>
    array (
      'type' => 'list',
      'title' => 'Recent Users',
      'size' => 'col-12',
      'model' => 'App\\Models\\User',
      'icon' => 'fas fa-user-plus',
      'description' => 'Latest 5 registered users',
      'limit' => 5,
      'sort' =>
      array (
        0 => 'created_at',
        1 => 'desc',
      ),
      'columns' =>
      array (
        0 =>
        array (
          'label' => 'Name',
          'field' => 'name',
        ),
        1 =>
        array (
          'label' => 'Email',
          'field' => 'email',
        ),
        2 =>
        array (
          'label' => 'Status',
          'field' => 'status',
          'format' => 'text',
        ),
      ),
      'width' => 6,
      'show_view_all' => true,
      'view_all_link' => '/admin/users',
    ),
    8 =>
    array (
      'type' => 'list',
      'title' => 'Roles (A–Z)',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Role',
      'icon' => 'fas fa-shield-alt',
      'description' => 'All roles alphabetically',
      'limit' => 5,
      'sort' =>
      array (
        0 => 'name',
        1 => 'asc',
      ),
      'columns' =>
      array (
        0 =>
        array (
          'label' => 'Name',
          'field' => 'name',
        ),
        1 =>
        array (
          'label' => 'Guard',
          'field' => 'guard_name',
        ),
        2 =>
        array (
          'label' => 'Editable',
          'field' => 'editable',
        ),
      ),
      'width' => 6,
      'show_view_all' => true,
      'view_all_link' => '/admin/roles',
    ),
    9 =>
    array (
      'type' => 'list',
      'title' => 'Recent Permissions',
      'size' => 'col-12',
      'model' => 'Spatie\\Permission\\Models\\Permission',
      'icon' => 'fas fa-lock',
      'description' => 'Latest created permissions',
      'limit' => 5,
      'sort' =>
      array (
        0 => 'created_at',
        1 => 'desc',
      ),
      'columns' =>
      array (
        0 =>
        array (
          'label' => 'Name',
          'field' => 'name',
        ),
        1 =>
        array (
          'label' => 'Guard',
          'field' => 'guard_name',
        ),
        2 =>
        array (
          'label' => 'Description',
          'field' => 'description',
          'truncate' => 40,
        ),
      ),
      'width' => 6,
      'show_view_all' => true,
      'view_all_link' => '/admin/access-control-management',
    ),
    10 =>
    array (
      'type' => 'action_card',
      'title' => 'Invite User',
      'size' => 'col-12',
      'icon' => 'fas fa-envelope',
      'description' => 'Send invitation to new user',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Invite',
          'event' => 'openDrawer',
          'params' =>
          array (
            'component' => 'qf.data-table-form',
            'params' =>
            array (
              'configKey' => 'admin.user',
              'recordId' => null,
            ),
            'title' => 'Invite User',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 3,
    ),
    11 =>
    array (
      'type' => 'action_card',
      'title' => 'Add Role',
      'size' => 'col-12',
      'icon' => 'fas fa-user-tag',
      'description' => 'Create a new role',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'openDrawer',
          'params' =>
          array (
            'component' => 'qf.data-table-form',
            'params' =>
            array (
              'configKey' => 'admin.role',
              'recordId' => null,
            ),
            'title' => 'Add Role',
          ),
          'style' => 'secondary',
        ),
      ),
      'width' => 3,
    ),
    12 =>
    array (
      'type' => 'action_card',
      'title' => 'Add Permission',
      'size' => 'col-12',
      'icon' => 'fas fa-key',
      'description' => 'Define a new permission',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'openDrawer',
          'params' =>
          array (
            'component' => 'qf.data-table-form',
            'params' =>
            array (
              'configKey' => 'admin.permission',
              'recordId' => null,
            ),
            'title' => 'Add Permission',
          ),
          'style' => 'secondary',
        ),
      ),
      'width' => 3,
    ),
    13 =>
    array (
      'type' => 'action_card',
      'title' => 'Sync Permissions',
      'size' => 'col-12',
      'icon' => 'fas fa-sync-alt',
      'description' => 'Refresh permission list from code',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Sync Now',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/permissions',
          ),
          'style' => 'warning',
        ),
      ),
      'width' => 3,
    ),
  ),
  'roles' =>
  array (
    'admin' => 'full',
    'super_admin' => 'full',
    'organization_manager' => 'limited',
  ),
  'layout' =>
  array (
    'columns' => 12,
    'gutter' => 3,
  ),
);