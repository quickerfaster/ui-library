<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;
use App\Modules\Hr\Models\PayrollRun;

class PayrollExecutiveSummary extends Component
{
    public PayrollRun $run;

    public function mount(PayrollRun $run)
    {
        $this->run = $run;
    }

    public function getCustomWidgets()
    {
        return [
            'title' => 'Executive Summary',
            'description' => "Payroll run {$this->run->period_start->format('M d, Y')} – {$this->run->period_end->format('M d, Y')}",
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
                    'conditions' => [['payroll_run_id', '=', $this->run->id]],
                ],
                [
                    'type' => 'stat',
                    'title' => 'Total Deductions',
                    'width' => 3,
                    'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                    'aggregate' => 'sum',
                    'field' => 'total_deductions',
                    'conditions' => [['payroll_run_id', '=', $this->run->id]],
                ],
                [
                    'type' => 'stat',
                    'title' => 'Total Taxes',
                    'width' => 3,
                    'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                    'aggregate' => 'sum',
                    'field' => 'total_taxes',
                    'conditions' => [['payroll_run_id', '=', $this->run->id]],
                ],
                [
                    'type' => 'stat',
                    'title' => 'Net Cash Required',
                    'width' => 3,
                    'model' => 'App\Modules\Hr\Models\PayrollPayslip',
                    'aggregate' => 'sum',
                    'field' => 'net_pay',
                    'conditions' => [['payroll_run_id', '=', $this->run->id]],
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
                    'conditions' => [['payroll_run_id', '=', $this->run->id]],
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
                    'conditions' => [['payroll_run_id', '=', $this->run->id]],
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
                    'conditions' => [['payroll_run_id', '=', $this->run->id]],
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
                    'conditions' => [['payroll_run_id', '=', $this->run->id]],
                ],
            ],
        ];
    }

    public function render()
    {
        return view('hr::livewire.payroll.payroll-executive-summary', [
            'run' => $this->run,
            'customWidgets' => $this->getCustomWidgets(),
        ]);
    }
}
