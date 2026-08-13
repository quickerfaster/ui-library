<?php

return array (
  'title' => 'Organization Overview',
  'description' => 'Monitor companies, branches, departments, divisions, locations, and teams across your organization',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Total Companies',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Company',
      'icon' => 'fas fa-building',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Total Branches',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Branch',
      'icon' => 'fas fa-code-branch',
      'aggregate' => 'count',
      'width' => 3,
    ),
    2 =>
    array (
      'type' => 'stat',
      'title' => 'Total Departments',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Department',
      'icon' => 'fas fa-sitemap',
      'aggregate' => 'count',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'stat',
      'title' => 'Total Locations',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Location',
      'icon' => 'fas fa-map-marker-alt',
      'aggregate' => 'count',
      'width' => 3,
    ),
    4 =>
    array (
      'type' => 'stat',
      'title' => 'Total Divisions',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Division',
      'icon' => 'fas fa-diagram-project',
      'aggregate' => 'count',
      'width' => 3,
    ),
    5 =>
    array (
      'type' => 'stat',
      'title' => 'Total Teams',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Team',
      'icon' => 'fas fa-users',
      'aggregate' => 'count',
      'width' => 3,
    ),
    6 =>
    array (
      'type' => 'stat',
      'title' => 'Business Units',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\BusinessUnit',
      'icon' => 'fas fa-briefcase',
      'aggregate' => 'count',
      'width' => 3,
    ),
    7 =>
    array (
      'type' => 'list',
      'title' => 'Recent Companies',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Company',
      'icon' => 'fas fa-building',
      'description' => 'Latest 5 companies',
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
          'label' => 'Code',
          'field' => 'code',
        ),
        2 =>
        array (
          'label' => 'Status',
          'field' => 'is_active',
          'format' => 'boolean',
        ),
      ),
      'width' => 6,
      'show_view_all' => true,
      'view_all_link' => '/organization/companies',
    ),
    8 =>
    array (
      'type' => 'action_card',
      'title' => 'Add Company',
      'size' => 'col-12',
      'icon' => 'fas fa-plus-circle',
      'description' => 'Register a new company in the organization',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/organization/companies',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 3,
    ),
    9 =>
    array (
      'type' => 'action_card',
      'title' => 'Manage Structure',
      'size' => 'col-12',
      'icon' => 'fas fa-sitemap',
      'description' => 'Configure departments and divisions',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Go',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/organization/departments',
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
    'hr_admin' => 'limited',
  ),
  'layout' =>
  array (
    'columns' => 12,
    'gutter' => 3,
  ),
);