<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payroll Run Summary</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
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
        }

        .header p {
            margin: 5px 0 0;
            color: #666;
        }

        .company-details {
            margin-bottom: 20px;
            font-size: 9pt;
        }

        .summary-card {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
        }

        .summary-row {
            display: flex;
            margin-bottom: 8px;
        }

        .summary-label {
            width: 35%;
            font-weight: bold;
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
        }

        td {
            border-bottom: 1px solid #ddd;
            padding: 6px 8px;
        }

        .total-row {
            font-weight: bold;
            background-color: #ecf0f1;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #95a5a6;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .no-print {
            text-align: center;
            margin-top: 20px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
                margin: 0;
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
        </div>


        <div class="company-details">
            <strong>Company:</strong> {{ $companyName }}<br>
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

        <div class="no-print">
            <button onclick="window.print()">🖨️ Print / Save as PDF</button>
            <button onclick="window.close()">✖ Close</button>
        </div>
    </div>
</body>

</html>
