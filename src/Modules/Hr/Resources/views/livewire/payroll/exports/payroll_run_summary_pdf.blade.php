<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payroll Run Summary</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 22pt;
            margin: 0;
            color: #2c3e50;
        }

        .header p {
            margin: 5px 0 0;
            color: #7f8c8d;
        }

        .company-details {
            margin-bottom: 20px;
            font-size: 9pt;
        }

        .summary-card {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }

        .summary-row {
            display: flex;
            margin-bottom: 8px;
        }

        .summary-label {
            width: 35%;
            font-weight: bold;
            color: #2c3e50;
        }

        .summary-value {
            width: 65%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background-color: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
        }

        td {
            border-bottom: 1px solid #ddd;
            padding: 6px 8px;
            font-size: 9pt;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #95a5a6;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .total-row {
            font-weight: bold;
            background-color: #ecf0f1;
        }



        @media print {
            th {
                background-color: #2c3e50 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .summary-card {
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Payroll Run Summary</h1>
            <p>{{ $run->paySchedule->name }} | {{ $run->period_start->format('M d, Y') }} –
                {{ $run->period_end->format('M d, Y') }}</p>
        </div>

        <div class="company-details">
            <strong>Company:</strong> {{ $companyName ?? 'Your Company' }}<br>
            <strong>Run ID:</strong> #{{ $run->id }}<br>
            <strong>Status:</strong> {{ ucfirst($run->status) }}<br>
            <strong>Generated:</strong> {{ now()->format('F j, Y, g:i a') }}
        </div>

        <div class="summary-card">
            <div class="summary-row">
                <div class="summary-label">Total Gross Pay:</div>
                <div class="summary-value">{{ $currencySymbol }}{{ number_format($run->total_gross_pay, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Deductions:</div>
                <div class="summary-value">{{ $currencySymbol }}{{ number_format($run->total_deductions, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Taxes:</div>
                <div class="summary-value">{{ $currencySymbol }}{{ number_format($run->total_taxes, 2) }}</div>
            </div>
            @if ($run->total_employer_contributions)
                <div class="summary-row">
                    <div class="summary-label">Employer Contributions:</div>
                    <div class="summary-value">
                        {{ $currencySymbol }}{{ number_format($run->total_employer_contributions, 2) }}</div>
                </div>
            @endif
            <div class="summary-row">
                <div class="summary-label"><strong>Net Cash Required:</strong></div>
                <div class="summary-value">
                    <strong>{{ $currencySymbol }}{{ number_format($run->total_cash_required, 2) }}</strong></div>
            </div>
        </div>

        <h3>Employee Payslips</h3>
        <table>
            <thead>
                <tr>
                    <th>Employee #</th>
                    <th>Name</th>
                    <th class="text-end">Gross Pay</th>
                    <th class="text-end">Deductions</th>
                    <th class="text-end">Net Pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($run->payslips as $payslip)
                    @php $employee = $payslip->employee; @endphp
                    <tr>
                        <td>{{ $employee->employee_number }}</td>
                        <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                        <td class="text-end">{{ $currencySymbol }}{{ number_format($payslip->gross_pay, 2) }}</td>
                        <td class="text-end">{{ $currencySymbol }}{{ number_format($payslip->total_deductions, 2) }}
                        </td>
                        <td class="text-end">{{ $currencySymbol }}{{ number_format($payslip->net_pay, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>Total</strong></td>
                    <td></td>
                    <td class="text-end">
                        <strong>{{ $currencySymbol }}{{ number_format($run->total_gross_pay, 2) }}</strong></td>
                    <td class="text-end">
                        <strong>{{ $currencySymbol }}{{ number_format($run->total_deductions, 2) }}</strong></td>
                    <td class="text-end">
                        <strong>{{ $currencySymbol }}{{ number_format($run->total_cash_required, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            This is a system‑generated report. For any discrepancies, please contact payroll.
        </div>
    </div>
</body>

</html>
