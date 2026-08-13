<?php

return array (
  'title' => 'Users & Permissions Overview',
  'description' => 'Monitor user accounts, roles, permissions, and access control metrics',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Total Users',
      'size' => 'col-12',
      'model' => config('ui-library.user.model', 'App\\Models\\User'),
      'icon' => 'fas fa-users',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Active Users',
      'size' => 'col-12',
      'model' => config('ui-library.user.model', 'App\\Models\\User'),
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
      'model' => 'App\\Modules\\Admin\\Models\\Role',
      'icon' => 'fas fa-user-tag',
      'aggregate' => 'count',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'stat',
      'title' => 'Total Permissions',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Admin\\Models\\Permission',
      'icon' => 'fas fa-key',
      'aggregate' => 'count',
      'width' => 3,
    ),
    4 =>
    array (
      'type' => 'chart',
      'title' => 'Users by Status',
      'size' => 'col-12',
      'model' => config('ui-library.user.model', 'App\\Models\\User'),
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
      'model' => 'App\\Modules\\Admin\\Models\\Role',
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
      'model' => 'App\\Modules\\Admin\\Models\\Permission',
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
      'model' => config('ui-library.user.model', 'App\\Models\\User'),
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
      'model' => 'App\\Modules\\Admin\\Models\\Role',
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
      'type' => 'action_card',
      'title' => 'Add New User',
      'size' => 'col-12',
      'icon' => 'fas fa-user-plus',
      'description' => 'Create a new user account',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/users',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 3,
    ),
    10 =>
    array (
      'type' => 'action_card',
      'title' => 'Manage Roles',
      'size' => 'col-12',
      'icon' => 'fas fa-user-shield',
      'description' => 'Create and configure roles',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Go',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/roles',
          ),
          'style' => 'secondary',
        ),
      ),
      'width' => 3,
    ),
  ),
  'roles' =>
  array (
    'admin' => 'full',
    'super_admin' => 'full',
  ),
  'layout' =>
  array (
    'columns' => 12,
    'gutter' => 3,
  ),
);