<?php

return array (
  'title' => 'Workflows Overview',
  'description' => 'Monitor workflow definitions and approval activity',
  'widgets' =>
  array (
    0 =>
    array (
      'type' => 'stat',
      'title' => 'Total Definitions',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\WorkflowDefinition',
      'icon' => 'fas fa-project-diagram',
      'aggregate' => 'count',
      'width' => 3,
    ),
    1 =>
    array (
      'type' => 'stat',
      'title' => 'Active Definitions',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\WorkflowDefinition',
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
      'title' => 'Total Workflows',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Workflow',
      'icon' => 'fas fa-code-branch',
      'aggregate' => 'count',
      'width' => 3,
    ),
    3 =>
    array (
      'type' => 'stat',
      'title' => 'Pending Approvals',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Workflow',
      'icon' => 'fas fa-hourglass-half',
      'aggregate' => 'count',
      'conditions' =>
      array (
        0 =>
        array (
          0 => 'status',
          1 => '=',
          2 => 'pending',
        ),
      ),
      'width' => 3,
    ),
    4 =>
    array (
      'type' => 'chart',
      'title' => 'Workflows by Status',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Workflow',
      'group_by' => 'status',
      'chart_type' => 'doughnut',
      'description' => 'Distribution of workflow statuses',
      'aggregate' => 'count',
      'width' => 6,
    ),
    5 =>
    array (
      'type' => 'trend',
      'title' => 'Workflow Activity (30 Days)',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Workflow',
      'group_by' => 'day',
      'icon' => 'fas fa-chart-line',
      'description' => 'Daily workflow activity count',
      'aggregate' => 'count',
      'date_field' => 'created_at',
      'period' => 30,
      'width' => 6,
    ),
    6 =>
    array (
      'type' => 'list',
      'title' => 'Recent Workflows',
      'size' => 'col-12',
      'model' => 'QuickerFaster\\UILibrary\\Models\\Workflow',
      'icon' => 'fas fa-list-alt',
      'description' => 'Latest 10 workflows',
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
          'label' => 'Definition Key',
          'field' => 'definition_key',
        ),
        1 =>
        array (
          'label' => 'Status',
          'field' => 'status',
          'format' => 'text',
        ),
        2 =>
        array (
          'label' => 'Submitted At',
          'field' => 'submitted_at',
          'format' => 'datetime',
        ),
      ),
      'width' => 12,
      'show_view_all' => true,
      'view_all_link' => '/admin/workflow-definitions',
    ),
    7 =>
    array (
      'type' => 'action_card',
      'title' => 'New Workflow',
      'size' => 'col-12',
      'icon' => 'fas fa-plus-circle',
      'description' => 'Create a new workflow definition with the step-by-step wizard.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'Create',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/workflow-definition-wizard',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 6,
    ),
    8 =>
    array (
      'type' => 'action_card',
      'title' => 'Workflow Definitions',
      'size' => 'col-12',
      'icon' => 'fas fa-list',
      'description' => 'View and manage all workflow definitions.',
      'actions' =>
      array (
        0 =>
        array (
          'label' => 'View',
          'event' => 'navigate',
          'params' =>
          array (
            'url' => '/admin/workflow-definitions',
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
