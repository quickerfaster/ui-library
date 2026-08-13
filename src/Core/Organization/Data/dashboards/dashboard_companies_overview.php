<?php

return array (
  'title' => 'Companies Overview',
  'description' => 'Monitor companies, branches, and business units across your organization',
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
      'title' => 'Active Companies',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Company',
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
      'title' => 'Total Branches',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Branch',
      'icon' => 'fas fa-code-branch',
      'aggregate' => 'count',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'stat',
      'title' => 'Business Units',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\BusinessUnit',
      'icon' => 'fas fa-briefcase',
      'aggregate' => 'count',
      'width' => 3,
    ),
    4 =>
    array (
      'type' => 'chart',
      'title' => 'Companies by Status',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Company',
      'group_by' => 'is_active',
      'chart_type' => 'pie',
      'description' => 'Active vs. inactive companies',
      'aggregate' => 'count',
      'width' => 4,
    ),
    5 =>
    array (
      'type' => 'chart',
      'title' => 'Branches by Company',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Branch',
      'group_by' => 'company_id',
      'chart_type' => 'bar',
      'description' => 'Branch count per company',
      'aggregate' => 'count',
      'width' => 4,
    ),
    6 =>
    array (
      'type' => 'chart',
      'title' => 'Business Units by Company',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\BusinessUnit',
      'group_by' => 'company_id',
      'chart_type' => 'bar',
      'description' => 'Business unit count per company',
      'aggregate' => 'count',
      'width' => 4,
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
      'type' => 'list',
      'title' => 'Recent Branches',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Core\\Organization\\Models\\Branch',
      'icon' => 'fas fa-code-branch',
      'description' => 'Latest 5 branches',
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
          'label' => 'Company',
          'field' => 'company.name',
        ),
      ),
      'width' => 6,
      'show_view_all' => true,
      'view_all_link' => '/organization/branches',
    ),
    9 =>
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
    10 =>
    array (
      'type' => 'action_card',
      'title' => 'Add Branch',
      'size' => 'col-12',
      'icon' => 'fas fa-code-branch',
      'description' => 'Create a new branch under a company',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/organization/branches',
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