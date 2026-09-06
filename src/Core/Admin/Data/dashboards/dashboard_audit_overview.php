<?php

return array (
  'title' => 'Audit Overview',
  'description' => 'Monitor activity logs, track user actions, and review system events',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Total Activities',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Admin\\Models\\ActivityLog',
      'icon' => 'fas fa-history',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Activities Today',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Admin\\Models\\ActivityLog',
      'icon' => 'fas fa-calendar-day',
      'aggregate' => 'count',
      'conditions' =>
      array (
        0 =>
        array (
          0 => 'created_at',
          1 => '>=',
          2 => 'today',
        ),
      ),
      'width' => 3,
    ),
    2 =>
    array (
      'type' => 'stat',
      'title' => 'Unique Users',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Admin\\Models\\ActivityLog',
      'icon' => 'fas fa-users',
      'aggregate' => 'count',
      'distinct' => 'causer_id',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'stat',
      'title' => 'Event Types',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Admin\\Models\\ActivityLog',
      'icon' => 'fas fa-tags',
      'aggregate' => 'count',
      'distinct' => 'event',
      'width' => 3,
    ),
    4 =>
    array (
      'type' => 'chart',
      'title' => 'Activities by Event',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Admin\\Models\\ActivityLog',
      'group_by' => 'event',
      'chart_type' => 'bar',
      'description' => 'Distribution of activity event types',
      'aggregate' => 'count',
      'width' => 6,
    ),
    5 =>
    array (
      'type' => 'trend',
      'title' => 'Activity Trend (Last 30 Days)',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Admin\\Models\\ActivityLog',
      'group_by' => 'day',
      'icon' => 'fas fa-chart-line',
      'description' => 'Daily activity count',
      'aggregate' => 'count',
      'date_field' => 'created_at',
      'period' => 30,
      'width' => 6,
    ),
    6 =>
    array (
      'type' => 'list',
      'title' => 'Recent Activities',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Admin\\Models\\ActivityLog',
      'icon' => 'fas fa-list-alt',
      'description' => 'Latest 10 activity records',
      'limit' => 10,
      'sort' =>
      array (
        0 => 'created_at',
        1 => 'desc',
      ),
      'columns' =>
      array (
        0 =>
        array (
          'label' => 'Event',
          'field' => 'event',
        ),
        1 =>
        array (
          'label' => 'Subject',
          'field' => 'subject_type',
        ),
        2 =>
        array (
          'label' => 'User',
          'field' => 'causer.name',
        ),
        3 =>
        array (
          'label' => 'Date',
          'field' => 'created_at',
          'format' => 'datetime',
        ),
      ),
      'width' => 12,
      'show_view_all' => true,
      'view_all_link' => '/admin/activity-logs',
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