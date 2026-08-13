<?php

return array (
  'title' => 'Locations Overview',
  'description' => 'Monitor office locations, sites, and geographical distribution',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Total Locations',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Location',
      'icon' => 'fas fa-map-marker-alt',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Active Locations',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Location',
      'icon' => 'fas fa-check-circle',
      'aggregate' => 'count',
      'conditions' =>
      array (
        0 =>
        array (
          0 => 'is_active',
          1 => '=',
          2 => true,
        ),
      ),
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
      'title' => 'Locations by Country',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Location',
      'group_by' => 'country',
      'chart_type' => 'bar',
      'description' => 'Active locations per country',
      'aggregate' => 'count',
      'conditions' =>
      array (
        0 =>
        array (
          0 => 'is_active',
          1 => '=',
          2 => true,
        ),
      ),
      'width' => 4,
    ),
    4 =>
    array (
      'type' => 'chart',
      'title' => 'Locations by Type',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Location',
      'group_by' => 'type',
      'chart_type' => 'pie',
      'description' => 'Distribution of location types',
      'aggregate' => 'count',
      'width' => 4,
    ),
    5 =>
    array (
      'type' => 'chart',
      'title' => 'Locations by Company',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Location',
      'group_by' => 'company_id',
      'chart_type' => 'bar',
      'description' => 'Location count per company',
      'aggregate' => 'count',
      'width' => 4,
    ),
    6 =>
    array (
      'type' => 'list',
      'title' => 'Recent Locations',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Location',
      'icon' => 'fas fa-location-dot',
      'description' => 'Latest 5 added locations',
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
          'label' => 'City',
          'field' => 'city',
        ),
        2 =>
        array (
          'label' => 'Country',
          'field' => 'country',
        ),
        3 =>
        array (
          'label' => 'Status',
          'field' => 'is_active',
          'format' => 'boolean',
        ),
      ),
      'width' => 6,
      'show_view_all' => true,
      'view_all_link' => '/organization/locations',
    ),
    7 =>
    array (
      'type' => 'list',
      'title' => 'Teams (A–Z)',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Team',
      'icon' => 'fas fa-people-group',
      'description' => 'All teams alphabetically',
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
          'label' => 'Department',
          'field' => 'department.name',
        ),
      ),
      'width' => 6,
      'show_view_all' => true,
      'view_all_link' => '/organization/teams',
    ),
    8 =>
    array (
      'type' => 'action_card',
      'title' => 'Add Location',
      'size' => 'col-12',
      'icon' => 'fas fa-plus-circle',
      'description' => 'Create a new office or site location',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/organization/locations',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 3,
    ),
    9 =>
    array (
      'type' => 'action_card',
      'title' => 'Add Team',
      'size' => 'col-12',
      'icon' => 'fas fa-people-group',
      'description' => 'Create a new team in the organization',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/organization/teams',
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