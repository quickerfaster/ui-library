<?php

return array (
  'title' => 'System Dashboard',
  'description' => 'Application settings, setup wizard, and system configuration',
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
      'title' => 'System Settings',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\SystemSetting',
      'icon' => 'fas fa-cog',
      'aggregate' => 'count',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'action_card',
      'title' => 'Setup Wizard',
      'size' => 'col-12',
      'icon' => 'fas fa-magic',
      'description' => 'Run the application setup wizard to configure your instance.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Start',
          'event' => 'openSetupWizard',
          'params' =>
          array (
            'type' => 'setup',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 3,
    ),
    4 =>
    array (
      'type' => 'action_card',
      'title' => 'General Settings',
      'size' => 'col-12',
      'icon' => 'fas fa-sliders-h',
      'description' => 'Configure application name, timezone, date format, and more.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Configure',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/system/settings',
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