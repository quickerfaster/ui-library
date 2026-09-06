<?php

return array (
  'title' => 'Security Overview',
  'description' => 'Monitor security settings, authentication, and access policies',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Active Sessions',
      'size' => 'col-12',
      'model' => config('ui-library.user.model', 'App\\Models\\User'),
      'icon' => 'fas fa-desktop',
      'aggregate' => 'count',
      'width' => 6,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Total Roles',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Role',
      'icon' => 'fas fa-shield-alt',
      'aggregate' => 'count',
      'width' => 6,
    ),
  ),
);