<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $payslip['number'] }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-info {
            flex: 2;
        }
        .payslip-info {
            flex: 1;
            text-align: right;
        }
        .section {
            margin: 15px 0;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #999;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .net-pay {
            font-size: 14px;
            font-weight: bold;
            color: #28a745;
            margin: 10px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #333;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-info">
            @if(file_exists($company['logo_path']))
                <img src="{{ $company['logo_path'] }}" style="height: 40px; margin-bottom: 10px;">
            @endif
            <h2>{{ $company['name'] }}</h2>
            <div>{{ $company['address'] }}</div>
            <div>Phone: {{ $company['phone'] }}</div>
        </div>
        <div class="payslip-info">
            <h3>PAYSLIP</h3>
            <div><strong>Number:</strong> {{ $payslip['number'] }}</div>
            <div><strong>Date:</strong> {{ $payslip['paid_at']?->format('M j, Y') ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- Employee & Pay Period -->
    <div class="section">
        <div><strong>Employee:</strong> {{ $employee['name'] }} ({{ $employee['id'] }})</div>
        <div><strong>Address:</strong> {{ $employee['address'] }}</div>
        <div><strong>Pay Period:</strong> {{ $payroll_run['period_start'] }} to {{ $payroll_run['period_end'] }}</div>
    </div>

    <!-- Earnings -->
    <div class="section">
        <div class="section-title">EARNINGS</div>
        <table>
            <tr>
                <td>Base Salary</td>
                <td class="text-right">${{ number_format($payslip['base_salary'], 2) }}</td>
            </tr>
            @if($payslip['overtime_pay'] > 0)
            <tr>
                <td>Overtime Pay</td>
                <td class="text-right">${{ number_format($payslip['overtime_pay'], 2) }}</td>
            </tr>
            @endif
            @if($payslip['bonus_amount'] > 0)
            <tr>
                <td>Bonus</td>
                <td class="text-right">${{ number_format($payslip['bonus_amount'], 2) }}</td>
            </tr>
            @endif
            @if($payslip['allowance_amount'] > 0)
            <tr>
                <td>Allowances</td>
                <td class="text-right">${{ number_format($payslip['allowance_amount'], 2) }}</td>
            </tr>
            @endif
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td>Gross Pay</td>
                <td class="text-right">${{ number_format($payslip['gross_pay'], 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Deductions -->
    <div class="section">
        <div class="section-title">DEDUCTIONS</div>
        <table>
            @if($payslip['tax_deductions'] > 0)
            <tr>
                <td>Income Tax</td>
                <td class="text-right">${{ number_format($payslip['tax_deductions'], 2) }}</td>
            </tr>
            @endif
            @if($payslip['benefit_deductions'] > 0)
            <tr>
                <td>Benefits</td>
                <td class="text-right">${{ number_format($payslip['benefit_deductions'], 2) }}</td>
            </tr>
            @endif
            @if($payslip['other_deductions'] > 0)
            <tr>
                <td>Other Deductions</td>
                <td class="text-right">${{ number_format($payslip['other_deductions'], 2) }}</td>
            </tr>
            @endif
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td>Total Deductions</td>
                <td class="text-right">${{ number_format($payslip['total_deductions'], 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Net Pay -->
    <div class="net-pay">
        NET PAY: ${{ number_format($payslip['net_pay'], 2) }}
    </div>

    <!-- Signatories -->
    <div class="section">
        <div class="section-title">AUTHORIZED SIGNATURES</div>
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div>Prepared By: {{ $payroll_run['prepared_by'] ?? 'N/A' }}</div>
                    <div style="margin-top: 40px; border-top: 1px solid #333;">Signature</div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div>Approved By: {{ $payroll_run['approved_by'] ?? 'N/A' }}</div>
                    <div style="margin-top: 40px; border-top: 1px solid #333;">Signature</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>This is a computer-generated payslip. No signature required.</div>
        <div>Confidential: For employee eyes only.</div>
    </div>
</body>
</html>
