<?php

return array (
  'title' => 'Structure Overview',
  'description' => 'Monitor departments, divisions, and organizational hierarchy',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Total Departments',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Department',
      'icon' => 'fas fa-sitemap',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Total Divisions',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Division',
      'icon' => 'fas fa-diagram-project',
      'aggregate' => 'count',
      'width' => 3,
    ),
    2 =>
    array (
      'type' => 'stat',
      'title' => 'Total Teams',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Team',
      'icon' => 'fas fa-users',
      'aggregate' => 'count',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'chart',
      'title' => 'Departments by Company',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Department',
      'group_by' => 'company_id',
      'chart_type' => 'bar',
      'description' => 'Department count per company',
      'aggregate' => 'count',
      'width' => 4,
    ),
    4 =>
    array (
      'type' => 'chart',
      'title' => 'Divisions by Department',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Division',
      'group_by' => 'department_id',
      'chart_type' => 'bar',
      'description' => 'Division count per department',
      'aggregate' => 'count',
      'width' => 4,
    ),
    5 =>
    array (
      'type' => 'chart',
      'title' => 'Teams by Department',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Team',
      'group_by' => 'department_id',
      'chart_type' => 'bar',
      'description' => 'Team count per department',
      'aggregate' => 'count',
      'width' => 4,
    ),
    6 =>
    array (
      'type' => 'list',
      'title' => 'Departments (A–Z)',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Department',
      'icon' => 'fas fa-layer-group',
      'description' => 'All departments alphabetically',
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
          'label' => 'Code',
          'field' => 'code',
        ),
        2 =>
        array (
          'label' => 'Company',
          'field' => 'company.name',
        ),
      ),
      'width' => 6,
      'show_view_all' => true,
      'view_all_link' => '/organization/departments',
    ),
    7 =>
    array (
      'type' => 'list',
      'title' => 'Recent Divisions',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Division',
      'icon' => 'fas fa-diagram-project',
      'description' => 'Latest 5 divisions',
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
          'label' => 'Department',
          'field' => 'department.name',
        ),
      ),
      'width' => 6,
      'show_view_all' => true,
      'view_all_link' => '/organization/divisions',
    ),
    8 =>
    array (
      'type' => 'action_card',
      'title' => 'Add Department',
      'size' => 'col-12',
      'icon' => 'fas fa-plus-circle',
      'description' => 'Create a new department in the organization',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/organization/departments',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 3,
    ),
    9 =>
    array (
      'type' => 'action_card',
      'title' => 'Add Division',
      'size' => 'col-12',
      'icon' => 'fas fa-diagram-project',
      'description' => 'Create a new division under a department',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/organization/divisions',
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
    'organization_manager' => 'limited',
  ),
  'layout' =>
  array (
    'columns' => 12,
    'gutter' => 3,
  ),
);