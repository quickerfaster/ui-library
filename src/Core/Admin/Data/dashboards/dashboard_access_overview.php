<?php

return array (
  'title' => 'Access Overview',
  'description' => 'Monitor roles, permissions, and access control configuration',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Total Roles',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Role',
      'icon' => 'fas fa-user-tag',
      'aggregate' => 'count',
      'width' => 4,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Total Permissions',
      'size' => 'col-12',
      'model' => 'Spatie\\Permission\\Models\\Permission',
      'icon' => 'fas fa-key',
      'aggregate' => 'count',
      'width' => 4,
    ),
    2 =>
    array (
      'type' => 'stat',
      'title' => 'Total Users',
      'size' => 'col-12',
      'model' => config('ui-library.user.model', 'App\\Models\\User'),
      'icon' => 'fas fa-users',
      'aggregate' => 'count',
      'width' => 4,
    ),
    3 =>
    array (
      'type' => 'chart',
      'title' => 'Roles by Guard',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Role',
      'group_by' => 'guard_name',
      'chart_type' => 'bar',
      'description' => 'Web vs. API roles',
      'aggregate' => 'count',
      'width' => 6,
    ),
    4 =>
    array (
      'type' => 'chart',
      'title' => 'Permissions by Guard',
      'size' => 'col-12',
      'model' => 'Spatie\\Permission\\Models\\Permission',
      'group_by' => 'guard_name',
      'chart_type' => 'bar',
      'description' => 'Web vs. API permissions',
      'aggregate' => 'count',
      'width' => 6,
    ),
    5 =>
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
      'width' => 12,
      'show_view_all' => true,
      'view_all_link' => '/admin/roles',
    ),
    6 =>
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
         'label' => 'Add Role',
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
         'style' => 'primary',
       ),
     ),
     'width' => 6,
   ),
    7 =>
    array (
      'type' => 'action_card',
      'title' => 'Access Control',
      'size' => 'col-12',
      'icon' => 'fas fa-shield-alt',
      'description' => 'Manage model-level access policies',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Go',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/access-control-management',
          ),
          'style' => 'secondary',
        ),
      ),
      'width' => 6,
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