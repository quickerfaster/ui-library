<?php

return array (
  'title' => 'System Deafult Dashboard Needs to be refactored as it is a duplicate of the hr modul dashboard',
  'description' => 'Employee statistics, hiring trends, and team analytics',
  'widgets' => 
  array (
    0 => 
    array (
      'type' => 'stat',
      'title' => 'Total Employees',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Hr\\Models\\Employee',
      'icon' => 'fas fa-users',
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
    1 => 
    array (
      'type' => 'chart',
      'title' => 'Attendance by Status',
      'size' => 'col-12',
      'model' => 'App\\Modules\\Hr\\Models\\Attendance',
      'group_by' => 'status',
      'chart_type' => 'bar',
      'aggregate' => 'count',
      'width' => 6,
    ),
    2 => 
    array (
      'type' => 'action_card',
      'title' => 'Process Payroll',
      'size' => 'col-12',
      'icon' => 'fas fa-calculator',
      'description' => 'Run monthly payroll for all employees.',
      'actions' => 
      array (
        0 => 
        array (
          'label' => 'Start',
          'event' => 'openPayrollWizard',
          'params' => 
          array (
            'month' => 'current',
          ),
          'style' => 'primary',
        ),
      ),
      'width' => 3,
    ),
  ),
  'roles' => 
  array (
    'admin' => 'full',
    'manager' => 'limited',
    'user' => 'basic',
  ),
);
