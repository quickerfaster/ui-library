<div>
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3>Payroll Run #{{ $run->id }}</h3>
            <p class="text-muted">
                {{ $run->paySchedule->name }} |
                {{ $run->period_start->format('M d, Y') }} – {{ $run->period_end->format('M d, Y') }}
            </p>
        </div>
        <div>
            @if ($canRecalculate)
                <button wire:click="confirmRecalculate" class="btn btn-sm btn-warning me-1">
                    <i class="fas fa-sync-alt"></i> Recalculate
                </button>
            @endif
            @if ($canApprove)
                <button wire:click="confirmApprove" class="btn btn-sm btn-success me-1">
                    <i class="fas fa-check-circle"></i> Approve
                </button>
            @endif
            @if ($canMarkPaid)
                <button wire:click="confirmMarkPaid" class="btn btn-sm btn-primary me-1">
                    <i class="fas fa-money-check"></i> Mark as Paid
                </button>
            @endif
            @if ($canCancel)
                <button wire:click="confirmCancel" class="btn btn-sm btn-danger me-1">
                    <i class="fas fa-ban"></i> Cancel
                </button>
            @endif
            <button wire:click="exportPayslips" class="btn btn-sm btn-secondary">
                <i class="fas fa-file-pdf"></i> Export Payslips
            </button>
        </div>
    </div>

    {{-- Status Banner --}}
    <div class="alert alert-{{ $run->status === 'paid' ? 'success' : ($run->status === 'approved' ? 'info' : ($run->status === 'cancelled' ? 'secondary' : 'warning')) }} mb-3">
        <strong>Status:</strong> {{ ucfirst($run->status) }}
        @if ($run->approved_at)
            | Approved by {{ $run->approved_by }} on {{ $run->approved_at->format('M d, Y H:i') }}
        @endif
        @if ($run->processed_at)
            | Paid on {{ $run->processed_at->format('M d, Y H:i') }}
        @endif
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        @foreach ($tabs as $key => $tab)
            <li class="nav-item">
                <button class="nav-link @if ($activeTab === $key) active @endif"
                    wire:click="$set('activeTab', '{{ $key }}')">
                    <i class="{{ $tab['icon'] }}"></i> {{ $tab['title'] }}
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        {{-- Overview Tab (unchanged) --}}
        @if ($activeTab === 'overview')
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">Period Details</div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Pay Schedule</dt>
                                <dd class="col-sm-8">{{ $run->paySchedule->name }}</dd>
                                <dt class="col-sm-4">Period Start</dt>
                                <dd class="col-sm-8">{{ $run->period_start->format('M d, Y') }}</dd>
                                <dt class="col-sm-4">Period End</dt>
                                <dd class="col-sm-8">{{ $run->period_end->format('M d, Y') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">Financial Totals</div>
                        <div class="card-body">
                            @php
                                $defaultCurrency = $run->paySchedule->currency_code ?? 'USD';
                                $currencySymbol = $this->getCurrencySymbol($defaultCurrency);
                            @endphp
                            <dl class="row">
                                <dt class="col-sm-6">Total Gross Pay</dt>
                                <dd class="col-sm-6 text-end">{{ $currencySymbol }}{{ number_format($run->total_gross_pay, 2) }}</dd>
                                <dt class="col-sm-6">Total Deductions</dt>
                                <dd class="col-sm-6 text-end">{{ $currencySymbol }}{{ number_format($run->total_deductions, 2) }}</dd>
                                <dt class="col-sm-6">Total Taxes</dt>
                                <dd class="col-sm-6 text-end">{{ $currencySymbol }}{{ number_format($run->total_taxes, 2) }}</dd>
                                <dt class="col-sm-6"><strong>Net Cash Required</strong></dt>
                                <dd class="col-sm-6 text-end"><strong>{{ $currencySymbol }}{{ number_format($run->total_cash_required, 2) }}</strong></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            @if ($run->notes)
                <div class="card mb-3">
                    <div class="card-header">Notes</div>
                    <div class="card-body">{{ $run->notes }}</div>
                </div>
            @endif
        @endif

        {{-- Payslips Tab (now using DataTable) --}}
        @if ($activeTab === 'payslips')
            <livewire:qf.data-table
                :configKey="'hr.payroll_run'"
                :customColumns="[
                    'employee_name' => [
                        'label' => 'Employee',
                        'field_type' => 'string',
                        'sortable' => true,
                        'render' => fn($payslip) => $payslip->employee->first_name . ' ' . $payslip->employee->last_name . ' (' . $payslip->employee->employee_number . ')',
                    ],
                    'gross_pay' => [
                        'label' => 'Gross Pay',
                        'field_type' => 'number',
                        'sortable' => true,
                        'render' => fn($payslip) => $this->getCurrencySymbol($payslip->employee->employeePosition->salary_currency ?? 'USD') . number_format($payslip->gross_pay, 2),
                    ],
                    'total_deductions' => [
                        'label' => 'Deductions',
                        'field_type' => 'number',
                        'sortable' => true,
                        'render' => fn($payslip) => $this->getCurrencySymbol($payslip->employee->employeePosition->salary_currency ?? 'USD') . number_format($payslip->total_deductions, 2),
                    ],
                    'net_pay' => [
                        'label' => 'Net Pay',
                        'field_type' => 'number',
                        'sortable' => true,
                        'render' => fn($payslip) => $this->getCurrencySymbol($payslip->employee->employeePosition->salary_currency ?? 'USD') . number_format($payslip->net_pay, 2),
                    ],
                ]"
                :queryFilters="['payroll_run_id' => $run->id]"
                :controls="[
                    'search' => true,
                    'perPage' => [10, 25, 50, 100],
                    'showHideColumns' => true,
                    'filterColumns' => true,
                ]"
                :simpleActions="['expand']"
                :expandConfig="[
                    'component' => 'qf.payslip-items',
                    'params' => [],
                    'label' => 'Items',
                    'icon' => 'fas fa-list',
                ]"
            />
        @endif

        {{-- Adjustments Tab (read-only DataTable) --}}
        @if ($activeTab === 'adjustments')
            <livewire:qf.data-table
                :configKey="'hr.payroll_run_adjustment'"
                :customColumns="[
                    'employee_name' => [
                        'label' => 'Employee',
                        'field_type' => 'string',
                        'sortable' => true,
                        'render' => fn($adjustment) => $adjustment->employee->first_name . ' ' . $adjustment->employee->last_name . ' (' . $adjustment->employee->employee_number . ')',
                    ],
                    'type' => [
                        'label' => 'Type',
                        'field_type' => 'select',
                        'sortable' => true,
                        'options' => [
                            'bonus' => 'Bonus',
                            'commission' => 'Commission',
                            'correction' => 'Correction',
                            'reimbursement' => 'Reimbursement',
                            'deduction' => 'Deduction',
                        ],
                        'render' => fn($adjustment) => ucfirst($adjustment->type),
                    ],
                    'label' => [
                        'label' => 'Description',
                        'field_type' => 'string',
                        'sortable' => false,
                    ],
                    'amount' => [
                        'label' => 'Amount',
                        'field_type' => 'number',
                        'sortable' => true,
                        'render' => fn($adjustment) => 
                            ($adjustment->type !== 'deduction' ? '+' : '-') . 
                            ' ' . $this->getCurrencySymbol($adjustment->employee->employeePosition->salary_currency ?? 'USD') . 
                            number_format($adjustment->amount, 2),
                    ],
                ]"
                :queryFilters="['payroll_run_id' => $run->id]"
                :controls="[
                    'search' => true,
                    'perPage' => [10, 25, 50, 100],
                    'bulkActions' => [
                        'delete' => true,
                        'export' => ['csv', 'pdf'],
                    ],
                    'showHideColumns' => true,
                    'filterColumns' => true,
                ]"
            />
        @endif

        {{-- Audit Tab (unchanged) --}}
        @if ($activeTab === 'audit')
            <div class="card">
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Created By</dt>
                        <dd class="col-sm-9">{{ $run->created_by ?? 'System' }}</dd>
                        <dt class="col-sm-3">Created At</dt>
                        <dd class="col-sm-9">{{ $run->created_at->format('M d, Y H:i:s') }}</dd>
                        <dt class="col-sm-3">Updated By</dt>
                        <dd class="col-sm-9">{{ $run->updated_by ?? '—' }}</dd>
                        <dt class="col-sm-3">Updated At</dt>
                        <dd class="col-sm-9">{{ $run->updated_at->format('M d, Y H:i:s') }}</dd>
                        <dt class="col-sm-3">Processed By</dt>
                        <dd class="col-sm-9">{{ $run->processed_by ?? '—' }}</dd>
                        <dt class="col-sm-3">Processed At</dt>
                        <dd class="col-sm-9">{{ $run->processed_at ? $run->processed_at->format('M d, Y H:i:s') : '—' }}</dd>
                        <dt class="col-sm-3">Approved By</dt>
                        <dd class="col-sm-9">{{ $run->approved_by ?? '—' }}</dd>
                        <dt class="col-sm-3">Approved At</dt>
                        <dd class="col-sm-9">{{ $run->approved_at ? $run->approved_at->format('M d, Y H:i:s') : '—' }}</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
</div>