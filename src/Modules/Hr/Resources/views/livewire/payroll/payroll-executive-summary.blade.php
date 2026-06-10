<x-qf::navigation-layout configKey="hr.payroll_run" context="payroll" moduleName="hr" :overrides="[
    'top_bar' => ['enabled' => false],
    'breadcrumb' => ['enabled' => false],
    'title' => ['enabled' => false],
    'titleRow' => ['enabled' => false],
    'context_menu' => ['enabled' => false],
]">

    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Executive Summary – {{ $run->paySchedule->name }} </h2>
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-print"></i> Print
                </button>
                <button onclick="window.close()" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>



        @php

            $customWidgets = [
                'title' => 'Executive Summary',
                'description' => "Payroll run {$run->period_start->format('M d, Y')} – {$run->period_end->format(
        'M d, Y',
    )}",
                'layout' => ['columns' => 12, 'gutter' => 3],
                'widgets' => [
                    // Stat cards
                    [
                        'type' => 'stat',
                        'title' => 'Total Gross Pay',
                        'width' => 3,
                        'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                        'aggregate' => 'sum',
                        'field' => 'gross_pay',
                        'conditions' => [['payroll_run_id', '=', $run->id]],
                    ],
                    [
                        'type' => 'stat',
                        'title' => 'Total Deductions',
                        'width' => 3,
                        'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                        'aggregate' => 'sum',
                        'field' => 'total_deductions',
                        'conditions' => [['payroll_run_id', '=', $run->id]],
                    ],
                    [
                        'type' => 'stat',
                        'title' => 'Total Taxes',
                        'width' => 3,
                        'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                        'aggregate' => 'sum',
                        'field' => 'total_taxes',
                        'conditions' => [['payroll_run_id', '=', $run->id]],
                    ],
                    [
                        'type' => 'stat',
                        'title' => 'Net Cash Required',
                        'width' => 3,
                        'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                        'aggregate' => 'sum',
                        'field' => 'net_pay',
                        'conditions' => [['payroll_run_id', '=', $run->id]],
                    ],
                    // Chart
                    [
                        'type' => 'chart',
                        'title' => 'Gross Pay by Department',
                        'width' => 6,
                        'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                        'group_by' => 'employee.employeePosition.department.name',
                        'aggregate' => 'sum',
                        'field' => 'gross_pay',
                        'chart_type' => 'bar',
                        'conditions' => [['payroll_run_id', '=', $run->id]],
                    ],
                    // Grouped tables using the custom grouped_list processor
                    [
                        'type' => 'grouped_list',
                        'title' => 'Summary by Company',
                        'width' => 12,
                        'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                        'group_by' => 'employee.company.name',
                        'aggregates' => [
                            'gross_pay' => 'sum',
                            'total_deductions' => 'sum',
                            'total_taxes' => 'sum',
                            'net_pay' => 'sum',
                            'employee_id' => 'count',
                        ],
                        'columns' => [
                            ['label' => 'Company', 'field' => 'group_label'],
                            ['label' => 'Employees', 'field' => 'employee_id_count', 'format' => 'number'],
                            ['label' => 'Gross Pay', 'field' => 'gross_pay_sum', 'format' => 'currency'],
                            ['label' => 'Deductions', 'field' => 'total_deductions_sum', 'format' => 'currency'],
                            ['label' => 'Taxes', 'field' => 'total_taxes_sum', 'format' => 'currency'],
                            ['label' => 'Net Pay', 'field' => 'net_pay_sum', 'format' => 'currency'],
                        ],
                        'conditions' => [['payroll_run_id', '=', $run->id]],
                        'sort' => ['group_label', 'asc'],
                    ],
                    [
                        'type' => 'grouped_list',
                        'title' => 'Summary by Department',
                        'width' => 6,
                        'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                        'group_by' => 'employee.employeePosition.department.name',
                        'aggregates' => [
                            'gross_pay' => 'sum',
                            'total_deductions' => 'sum',
                            'net_pay' => 'sum',
                            'employee_id' => 'count',
                        ],
                        'columns' => [
                            ['label' => 'Department', 'field' => 'group_label'],
                            ['label' => 'Employees', 'field' => 'employee_id_count', 'format' => 'number'],
                            ['label' => 'Gross Pay', 'field' => 'gross_pay_sum', 'format' => 'currency'],
                            ['label' => 'Deductions', 'field' => 'total_deductions_sum', 'format' => 'currency'],
                            ['label' => 'Net Pay', 'field' => 'net_pay_sum', 'format' => 'currency'],
                        ],
                        'conditions' => [['payroll_run_id', '=', $run->id]],
                    ],
                    [
                        'type' => 'grouped_list',
                        'title' => 'Summary by Location',
                        'width' => 6,
                        'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                        'group_by' => 'employee.employeePosition.location.name',
                        'aggregates' => [
                            'gross_pay' => 'sum',
                            'total_deductions' => 'sum',
                            'net_pay' => 'sum',
                            'employee_id' => 'count',
                        ],
                        'columns' => [
                            ['label' => 'Location', 'field' => 'group_label'],
                            ['label' => 'Employees', 'field' => 'employee_id_count', 'format' => 'number'],
                            ['label' => 'Gross Pay', 'field' => 'gross_pay_sum', 'format' => 'currency'],
                            ['label' => 'Deductions', 'field' => 'total_deductions_sum', 'format' => 'currency'],
                            ['label' => 'Net Pay', 'field' => 'net_pay_sum', 'format' => 'currency'],
                        ],
                        'conditions' => [['payroll_run_id', '=', $run->id]],
                    ],
                ],
            ];

        @endphp


        @livewire('qf.dashboard', [
            'configKey' => '', // not used because we provide customWidgets
            'parameters' => [],
            'customWidgets' => $customWidgets,
        ])
    </div>
</x-qf::navigation-layout>
