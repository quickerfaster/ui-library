<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Summary by {{ ucfirst($groupBy) }}</title>
    <style>
        /* Your existing CSS – add .text-end, etc. */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 22pt; }
        .summary-table, .detail-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .summary-table th, .summary-table td, .detail-table th, .detail-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        .summary-table th, .detail-table th { background-color: #2c3e50; color: white; }
        .text-end { text-align: right; }
        .subtotal-row, .total-row { background-color: #f2f2f2; font-weight: bold; }
        .no-print { text-align: center; margin-top: 30px; }
        @media print {
            th { background-color: #2c3e50 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $run->paySchedule->name }} – Payroll Summary by {{ ucfirst($groupBy) }}</h1>
    <p>{{ $run->period_start->format('M d, Y') }} – {{ $run->period_end->format('M d, Y') }}</p>
    <p>Generated: {{ now()->format('F j, Y, g:i a') }}</p>
</div>


{{-- Sticky print/close buttons --}}
<div class="no-print sticky-top bg-white pt-2 pb-2 border-bottom mb-3" style="top: 0; z-index: 1020;">
    <div class="d-flex justify-content-end gap-2">
        <button onclick="window.print()" class="btn btn-sm btn-secondary">
            <i class="fas fa-print"></i> 🖨️ Print / Save as PDF
        </button>
        <button onclick="window.close()" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-times"></i>✖ Close
        </button>
    </div>
</div>


@php
    $groupTotals = [];
    foreach ($groups as $groupName => $payslips) {
        $groupTotals[$groupName] = [
            'count' => $payslips->count(),
            'gross' => $payslips->sum('gross_pay'),
            'deductions' => $payslips->sum('total_deductions'),
            'taxes' => $payslips->sum('total_taxes'),
            'employer_contributions' => $payslips->sum('employer_contribution_total'),
            'net' => $payslips->sum('net_pay'),
        ];
    }

    $grandGross = array_sum(array_column($groupTotals, 'gross'));
    $grandDeductions = array_sum(array_column($groupTotals, 'deductions'));
    $grandTaxes = array_sum(array_column($groupTotals, 'taxes'));
    $grandEmployer = array_sum(array_column($groupTotals, 'employer_contributions'));
    $grandNet = array_sum(array_column($groupTotals, 'net'));

@endphp

{{-- Summary Table (with extra columns) --}}
<h2>Summary Totals by {{ ucfirst($groupBy) }}</h2>
<table class="summary-table">
    <thead>
        <tr>
            <th>{{ ucfirst($groupBy) }}</th>
            <th class="text-end">Employees</th>
            <th class="text-end">Gross Pay</th>
            <th class="text-end">Deductions</th>
            <th class="text-end">Taxes</th>
            <th class="text-end">Employer Contributions</th>
            <th class="text-end">Net Pay</th>
        </tr>
    </thead>
    <tbody>
        @foreach($groupTotals as $groupName => $totals)
        <tr>
            <td>{{ $groupName }}</td>
            <td class="text-end">{{ $totals['count'] }}</td>
            <td class="text-end">{{ number_format($totals['gross'], 2) }}</td>
            <td class="text-end">{{ number_format($totals['deductions'], 2) }}</td>
            <td class="text-end">{{ number_format($totals['taxes'], 2) }}</td>
            <td class="text-end">{{ number_format($totals['employer_contributions'], 2) }}</td>
            <td class="text-end">{{ number_format($totals['net'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td><strong>GRAND TOTAL</strong></td>
            <td class="text-end"><strong>{{ array_sum(array_column($groupTotals, 'count')) }}</strong></td>
            <td class="text-end"><strong>{{ number_format($grandGross, 2) }}</strong></td>
            <td class="text-end"><strong>{{ number_format($grandDeductions, 2) }}</strong></td>
            <td class="text-end"><strong>{{ number_format($grandTaxes, 2) }}</strong></td>
            <td class="text-end"><strong>{{ number_format($grandEmployer, 2) }}</strong></td>
            <td class="text-end"><strong>{{ number_format($grandNet, 2) }}</strong></td>
        </tr>
    </tfoot>
</table>
<br /><br />
{{-- Detailed Tables (per group) – also include taxes and employer columns --}}
<h2>Detailed Breakdown by {{ ucfirst($groupBy) }}</h2>
@foreach($groups as $groupName => $payslips)
    @php
        $groupGross = $payslips->sum('gross_pay');
        $groupDeductions = $payslips->sum('total_deductions');
        $groupTaxes = $payslips->sum('total_taxes');
        $groupEmployer = $payslips->sum('employer_contribution_total');
        $groupNet = $payslips->sum('net_pay');
    @endphp

    <div class="group-header">{{ ucfirst($groupBy) }}: {{ $groupName }} ({{ $payslips->count() }} employees)</div>
    <table class="detail-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th class="text-end">Gross Pay</th>
                <th class="text-end">Deductions</th>
                <th class="text-end">Taxes</th>
                <th class="text-end">Employer Contribution</th>
                <th class="text-end">Net Pay</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payslips as $payslip)
                @php $emp = $payslip->employee; @endphp
                <tr>
                    <td>{{ $emp->first_name }} {{ $emp->last_name }} (#{{ $emp->employee_number }})</td>
                    <td class="text-end">{{ number_format($payslip->gross_pay, 2) }}</td>
                    <td class="text-end">{{ number_format($payslip->total_deductions, 2) }}</td>
                    <td class="text-end">{{ number_format($payslip->total_taxes, 2) }}</td>
                    <td class="text-end">{{ number_format($payslip->employer_contribution_total ?? 0, 2) }}</td>
                    <td class="text-end">{{ number_format($payslip->net_pay, 2) }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td><strong>Subtotal for {{ $groupName }}</strong></td>
                <td class="text-end"><strong>{{ number_format($groupGross, 2) }}</strong></td>
                <td class="text-end"><strong>{{ number_format($groupDeductions, 2) }}</strong></td>
                <td class="text-end"><strong>{{ number_format($groupTaxes, 2) }}</strong></td>
                <td class="text-end"><strong>{{ number_format($groupEmployer, 2) }}</strong></td>
                <td class="text-end"><strong>{{ number_format($groupNet, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    <div style="margin-bottom: 20px;"></div>
@endforeach



<div class="no-print">
    <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    <button onclick="window.close()">✖ Close</button>
</div>
</body>
</html>
