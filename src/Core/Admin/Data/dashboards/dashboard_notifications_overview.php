<?php

return array (
  'title' => 'Notifications Overview',
  'description' => 'Monitor notification delivery, templates, and activity',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Total Notifications',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Notification',
      'icon' => 'fas fa-bell',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Unread Notifications',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Notification',
      'icon' => 'fas fa-envelope',
      'aggregate' => 'count',
      'conditions' =>
      array (
        0 =>
        array (
          0 => 'read_at',
          1 => '=',
          2 => NULL,
        ),
      ),
      'width' => 3,
    ),
    2 =>
    array (
      'type' => 'stat',
      'title' => 'Total Templates',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\NotificationTemplate',
      'icon' => 'fas fa-file-alt',
      'aggregate' => 'count',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'stat',
      'title' => 'Failed Deliveries',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\NotificationLog',
      'icon' => 'fas fa-exclamation-triangle',
      'aggregate' => 'count',
      'conditions' =>
      array (
        0 =>
        array (
          0 => 'status',
          1 => '=',
          2 => 'failed',
        ),
      ),
      'width' => 3,
    ),
    4 =>
    array (
      'type' => 'chart',
      'title' => 'Notifications by Type',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Notification',
      'group_by' => 'type',
      'chart_type' => 'doughnut',
      'description' => 'Distribution of notification types',
      'aggregate' => 'count',
      'width' => 6,
    ),
    5 =>
    array (
      'type' => 'chart',
      'title' => 'Notifications by Channel',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Notification',
      'group_by' => 'channel',
      'chart_type' => 'doughnut',
      'description' => 'Distribution of notification channels',
      'aggregate' => 'count',
      'width' => 6,
    ),
    6 =>
    array (
      'type' => 'trend',
      'title' => 'Notification Activity (30 Days)',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Notification',
      'group_by' => 'day',
      'icon' => 'fas fa-chart-line',
      'description' => 'Daily notification activity count',
      'aggregate' => 'count',
      'date_field' => 'created_at',
      'period' => 30,
      'width' => 6,
    ),
    7 =>
    array (
      'type' => 'list',
      'title' => 'Recent Notifications',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Notification',
      'icon' => 'fas fa-list-alt',
      'description' => 'Latest 10 notifications',
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
          'label' => 'Type',
          'field' => 'type',
          'format' => 'text',
        ),
        1 =>
        array (
          'label' => 'Channel',
          'field' => 'channel',
          'format' => 'text',
        ),
        2 =>
        array (
          'label' => 'Subject',
          'field' => 'subject',
        ),
      ),
      'width' => 12,
      'show_view_all' => true,
      'view_all_link' => '/admin/notifications',
    ),
    8 =>
    array (
      'type' => 'action_card',
      'title' => 'Notification Logs',
      'size' => 'col-12',
      'icon' => 'fas fa-history',
      'description' => 'View delivery logs and troubleshoot failed notifications.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'View',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/notification-logs',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 6,
    ),
    9 =>
    array (
      'type' => 'action_card',
      'title' => 'Preferences',
      'size' => 'col-12',
      'icon' => 'fas fa-sliders-h',
      'description' => 'Manage notification preferences and channel settings.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Manage',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/notification-preferences',
          ),
          'style' => 'info',
        ),
      ),
      'width' => 6,
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