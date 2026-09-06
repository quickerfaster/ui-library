<?php

return array (
  'title' => 'Setup Overview',
  'description' => 'Application setup wizard, onboarding configuration, and guided tours',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Setup Steps',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Common\\Models\\AppSetup',
      'icon' => 'fas fa-list-ol',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Completed Setups',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Common\\Models\\AppSetup',
      'icon' => 'fas fa-check-circle',
      'aggregate' => 'count',
      'conditions' =>
      array (
        0 =>
        array (
          0 => 'is_completed',
          1 => '=',
          2 => true,
        ),
      ),
      'width' => 3,
    ),
    2 =>
    array (
      'type' => 'stat',
      'title' => 'Onboarding Steps',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Common\\Models\\AppOnboarding',
      'icon' => 'fas fa-tasks',
      'aggregate' => 'count',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'stat',
      'title' => 'Active Tours',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Common\\Models\\AppTour',
      'icon' => 'fas fa-map-signs',
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
    4 =>
    array (
      'type' => 'action_card',
      'title' => 'Setup Wizard',
      'size' => 'col-12',
      'icon' => 'fas fa-magic',
      'description' => 'Run the step-by-step application setup wizard to configure your instance.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Start Wizard',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/setup/wizard',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 4,
    ),
    5 =>
    array (
      'type' => 'action_card',
      'title' => 'Onboarding',
      'size' => 'col-12',
      'icon' => 'fas fa-rocket',
      'description' => 'Configure the onboarding flow for new users.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Configure',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/system/onboarding',
          ),
          'style' => 'secondary',
        ),
      ),
      'width' => 4,
    ),
    6 =>
    array (
      'type' => 'action_card',
      'title' => 'Guided Tours',
      'size' => 'col-12',
      'icon' => 'fas fa-map-signs',
      'description' => 'Manage in-app guided tours for users.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Manage',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/system/tours',
          ),
          'style' => 'secondary',
        ),
      ),
      'width' => 4,
    ),
    7 =>
    array (
      'type' => 'list',
      'title' => 'Setup Steps',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Common\\Models\\AppSetup',
      'icon' => 'fas fa-list-ol',
      'description' => 'All setup steps and their status',
      'limit' => 10,
      'sort' =>
      array (
        0 => 'order',
        1 => 'asc',
      ),
      'columns' =>
      array (
        0 =>
        array (
          'label' => 'Step',
          'field' => 'name',
        ),
        1 =>
        array (
          'label' => 'Description',
          'field' => 'description',
          'truncate' => 50,
        ),
        2 =>
        array (
          'label' => 'Completed',
          'field' => 'is_completed',
          'format' => 'boolean',
        ),
      ),
      'width' => 12,
      'show_view_all' => true,
      'view_all_link' => '/setup/wizard',
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