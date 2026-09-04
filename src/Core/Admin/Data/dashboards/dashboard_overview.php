<?php

return array (
  'title' => 'Dashboard Overview',
  'description' => 'System-wide overview with key metrics and recent activity',
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
      'width' => 4,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Total Roles',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Role',
      'icon' => 'fas fa-user-tag',
      'aggregate' => 'count',
      'width' => 4,
    ),
    2 =>
    array (
      'type' => 'stat',
      'title' => 'Total Permissions',
      'size' => 'col-12',
      'model' => 'Spatie\\Permission\\Models\\Permission',
      'icon' => 'fas fa-key',
      'aggregate' => 'count',
      'width' => 4,
    ),
  ),
);