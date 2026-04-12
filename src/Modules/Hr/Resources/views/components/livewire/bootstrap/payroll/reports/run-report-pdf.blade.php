<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .summary { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #999; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Payroll Report</h2>
        <p><strong>{{ $header['title'] }}</strong></p>
        <p>Payroll Number: {{ $header['payroll_number'] }} |
           Period: {{ $header['pay_period_start'] }} to {{ $header['pay_period_end'] }} |
           Status: {{ ucfirst($header['status']) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Gross Pay</th>
                <th>Deductions</th>
                <th>Net Pay</th>
                <th>Payslip #</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
            <tr>
                <td>{{ $emp['employee_name'] }} ({{ $emp['employee_number'] }})</td>
                <td class="text-right">${{ number_format($emp['gross_pay'], 2) }}</td>
                <td class="text-right">${{ number_format($emp['total_deductions'], 2) }}</td>
                <td class="text-right">${{ number_format($emp['net_pay'], 2) }}</td>
                <td>{{ $emp['payslip_number'] }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>TOTALS</strong></td>
                <td class="text-right">${{ number_format($summary['total_gross'], 2) }}</td>
                <td class="text-right">${{ number_format($summary['total_deductions'], 2) }}</td>
                <td class="text-right">${{ number_format($summary['total_net'], 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Prepared By:</strong> {{ $header['prepared_by'] }} on {{ $header['prepared_at']?->format('M j, Y') }}</p>
        <p><strong>Approved By:</strong> {{ $header['approved_by'] }} on {{ $header['approved_at']?->format('M j, Y') }}</p>
    </div>
</body>
</html>
