<x-layout>
    <x-slot name="topNav">
        <livewire:qf.top-nav moduleName="hr">
    </x-slot>

    <x-slot name="sidebar">
        <livewire:qf.sidebar context="payroll"  moduleName="hr">
    </x-slot>

    <x-slot name="bottomBar">
        <livewire:qf.bottom-bar context="payroll" moduleName="hr">
    </x-slot>


    <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="d-inline-block ms-3">Payroll Report</h2>



        <!-- Download Buttons -->
        <div>
            <!-- Back Button -->
            <a href="/hr/payroll-runs" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Pay Runs
            </a>
            <a href="{{ route('payroll.reports.download.pdf', $payrollRun) }}" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
            <a href="{{ route('payroll.reports.download.excel', $payrollRun) }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-excel"></i> Download Excel
            </a>
        </div>
    </div>

        <!-- Header -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">{{ $reportData['header']['title'] }}</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Payroll Number:</strong> {{ $reportData['header']['payroll_number'] }}</p>
                        <p><strong>Pay Period:</strong> {{ $reportData['header']['pay_period_start'] }} to {{ $reportData['header']['pay_period_end'] }}</p>
                        <p><strong>Status:</strong>
                            <span class="badge bg-{{ $reportData['header']['status'] === 'paid' ? 'success' : 'info' }}">
                                {{ ucfirst($reportData['header']['status']) }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p><strong>Prepared By:</strong> {{ $reportData['header']['prepared_by'] }}<br>
                        <small>{{ $reportData['header']['prepared_at']?->format('M j, Y g:i A') }}</small></p>
                        <p><strong>Approved By:</strong> {{ $reportData['header']['approved_by'] }}<br>
                        <small>{{ $reportData['header']['approved_at']?->format('M j, Y g:i A') }}</small></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Table -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Employees Paid</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Payslip</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['employees'] as $emp)
                        <tr>
                            <td>
                                {{ $emp['employee_name'] }}<br>
                                <small class="text-muted">{{ $emp['employee_number'] }}</small>
                            </td>
                            <td>${{ number_format($emp['gross_pay'], 2) }}</td>
                            <td>${{ number_format($emp['total_deductions'], 2) }}</td>
                            <td><strong>${{ number_format($emp['net_pay'], 2) }}</strong></td>
<td>




<!-- Best practice: Two buttons -->
<a href="{{ route('payslips.view', $emp['payslip_id']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
    <i class="fas fa-eye"></i> View
</a>
{{-- - --<a href="{{ route('payslips.download', $emp['payslip_id']) }}" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-download"></i> Download
</a>--}}


</td>








                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Summary</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h6>Total Gross Pay</h6>
                        <h4 class="text-primary">${{ number_format($reportData['summary']['total_gross'], 2) }}</h4>
                    </div>
                    <div class="col-md-4">
                        <h6>Total Deductions</h6>
                        <h4 class="text-warning">${{ number_format($reportData['summary']['total_deductions'], 2) }}</h4>
                    </div>
                    <div class="col-md-4">
                        <h6>Total Net Pay</h6>
                        <h4 class="text-success">${{ number_format($reportData['summary']['total_net'], 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>


    </x-layouts>
