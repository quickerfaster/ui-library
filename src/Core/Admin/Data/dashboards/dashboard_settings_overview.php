<?php

return array (
  'title' => 'General Settings Overview',
  'description' => 'Configure system-wide settings, application preferences, and global defaults',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'System Settings',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\SystemSetting',
      'icon' => 'fas fa-cogs',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'App Settings',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Common\\Models\\AppGeneralSetting',
      'icon' => 'fas fa-sliders-h',
      'aggregate' => 'count',
      'width' => 3,
    ),
    2 =>
    array (
      'type' => 'action_card',
      'title' => 'System Settings',
      'size' => 'col-12',
      'icon' => 'fas fa-cog',
      'description' => 'Manage system-level configuration including application name, timezone, and date format.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Configure',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/system-settings',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 4,
    ),
    3 =>
    array (
      'type' => 'action_card',
      'title' => 'General Settings',
      'size' => 'col-12',
      'icon' => 'fas fa-sliders-h',
      'description' => 'Configure general application preferences and defaults.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Configure',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/general-settings',
          ),
          'style' => 'secondary',
        ),
      ),
      'width' => 4,
    ),
    4 =>
    array (
      'type' => 'action_card',
      'title' => 'Onboarding',
      'size' => 'col-12',
      'icon' => 'fas fa-rocket',
      'description' => 'Configure the application onboarding experience for new users.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Configure',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/onboarding',
          ),
          'style' => 'secondary',
        ),
      ),
      'width' => 4,
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