<div>
    {{-- Header and action buttons --}}
    <div class="d-flex justify-content-between align-items-start mb-3 mt-4">
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
            @if ($canMarkPaid || $run->status === 'approved')
                <button wire:click="generateBankFile" class="btn btn-sm btn-outline-primary me-1">
                    <i class="fas fa-file-export"></i> Bank File
                </button>
            @endif

            <button wire:click="queueSummaryPdf" class="btn btn-sm btn-secondary me-1">
                <i class="fas fa-file-pdf"></i> Summary PDF
            </button>

            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-print"></i> Reports
                </button>
                <ul class="dropdown-menu">
<li><a class="dropdown-item" href="{{ route('payroll-run.executive-summary', $run->id) }}" target="_blank">
    <i class="fas fa-chart-pie"></i> Executive Summary
</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li><a class="dropdown-item" href="{{ route('payroll-run.print-summary', $run->id) }}"
                            target="_blank">Full Employee List (Detailed)</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item"
                            href="{{ route('payroll-run.summary-grouped', [$run->id, 'group_by' => 'department']) }}"
                            target="_blank">Summary by Department</a></li>
                    <li><a class="dropdown-item"
                            href="{{ route('payroll-run.summary-grouped', [$run->id, 'group_by' => 'location']) }}"
                            target="_blank">Summary by Location</a></li>
                    <li><a class="dropdown-item"
                            href="{{ route('payroll-run.summary-grouped', [$run->id, 'group_by' => 'company']) }}"
                            target="_blank">Summary by Company</a></li>
                </ul>
            </div>


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
    <div
        class="alert alert-{{ $run->status === 'paid' ? 'success' : ($run->status === 'approved' ? 'info' : ($run->status === 'cancelled' ? 'secondary' : 'warning')) }} mb-3">
        <strong>Status:</strong> {{ ucfirst($run->status) }}
        @if ($run->approved_at)
            | Approved by {{ $run->approvedByUser?->name ?? $run->approved_by }} on
            {{ $run->approved_at->format('M d, Y H:i') }}
        @endif
        @if ($run->processed_at)
            | Paid on {{ $run->processed_at->format('M d, Y H:i') }}
        @endif
        @if ($run->payment_batch_id)
            | Batch: {{ $run->payment_batch_id }}
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
        {{-- Overview Tab --}}
        @if ($activeTab === 'overview')
            <div class="row g-4">
                {{-- Period Details Card --}}
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                            <h5 class="fw-bold text-primary mb-0">Period Details</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row gy-3">
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Pay Schedule</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->paySchedule->name }}
                                </div>

                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Period Start</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->period_start->format('M d, Y') }}
                                </div>

                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Period End</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->period_end->format('M d, Y') }}
                                </div>

                                @if ($run->base_currency)
                                    <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Base Currency
                                    </div>
                                    <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                        {{ $run->base_currency }}
                                    </div>
                                @endif

                                @if ($run->payment_date)
                                    <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Payment Date</div>
                                    <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                        {{ $run->payment_date->format('M d, Y') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Financial Totals Card (extended) --}}
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                            <h5 class="fw-bold text-primary mb-0">Financial Totals</h5>
                        </div>
                        <div class="card-body p-4">
                            @php
                                $defaultCurrency = $run->paySchedule->currency_code ?? 'USD';
                                $currencySymbol = $this->getCurrencySymbol($defaultCurrency);
                            @endphp
                            <div class="row gy-3">
                                <div class="col-sm-5 text-muted fw-semibold small text-uppercase">Total Gross Pay</div>
                                <div class="col-sm-7 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $currencySymbol }}{{ number_format($run->total_gross_pay, 2) }}
                                </div>

                                <div class="col-sm-5 text-muted fw-semibold small text-uppercase">Total Deductions</div>
                                <div class="col-sm-7 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $currencySymbol }}{{ number_format($run->total_deductions, 2) }}
                                </div>

                                <div class="col-sm-5 text-muted fw-semibold small text-uppercase">Total Taxes</div>
                                <div class="col-sm-7 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $currencySymbol }}{{ number_format($run->total_taxes, 2) }}
                                </div>

                                @if (isset($run->total_employer_contributions))
                                    <div class="col-sm-5 text-muted fw-semibold small text-uppercase">Employer
                                        Contributions</div>
                                    <div class="col-sm-7 text-dark fw-medium border-bottom pb-2 border-light">
                                        {{ $currencySymbol }}{{ number_format($run->total_employer_contributions, 2) }}
                                    </div>
                                @endif

                                @if (isset($run->total_employee_contributions))
                                    <div class="col-sm-5 text-muted fw-semibold small text-uppercase">Employee
                                        Contributions</div>
                                    <div class="col-sm-7 text-dark fw-medium border-bottom pb-2 border-light">
                                        {{ $currencySymbol }}{{ number_format($run->total_employee_contributions, 2) }}
                                    </div>
                                @endif

                                <div class="col-sm-5 text-muted fw-semibold small text-uppercase">Net Cash Required
                                </div>
                                <div class="col-sm-7 text-dark fw-medium border-bottom pb-2 border-light">
                                    <strong>{{ $currencySymbol }}{{ number_format($run->total_cash_required, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment & Reconciliation Card (new) --}}
                @if ($run->reconciliation_status || $run->payment_batch_id || $run->reconciled_at)
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                                <h5 class="fw-bold text-primary mb-0">Payment & Reconciliation</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row gy-3">
                                    @if ($run->payment_batch_id)
                                        <div class="col-sm-3 text-muted fw-semibold small text-uppercase">Batch ID</div>
                                        <div class="col-sm-9 text-dark fw-medium border-bottom pb-2 border-light">
                                            {{ $run->payment_batch_id }}
                                        </div>
                                    @endif
                                    @if ($run->reconciliation_status)
                                        <div class="col-sm-3 text-muted fw-semibold small text-uppercase">Reconciliation
                                            Status</div>
                                        <div class="col-sm-9 text-dark fw-medium border-bottom pb-2 border-light">
                                            <span
                                                class="badge bg-{{ $run->reconciliation_status === 'reconciled' ? 'success' : ($run->reconciliation_status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($run->reconciliation_status) }}
                                            </span>
                                        </div>
                                    @endif
                                    @if ($run->reconciled_at)
                                        <div class="col-sm-3 text-muted fw-semibold small text-uppercase">Reconciled At
                                        </div>
                                        <div class="col-sm-9 text-dark fw-medium border-bottom pb-2 border-light">
                                            {{ $run->reconciled_at->format('M d, Y H:i:s') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Notes Card --}}
                @if ($run->notes)
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                                <h5 class="fw-bold text-primary mb-0">Notes</h5>
                            </div>
                            <div class="card-body p-4">
                                {{ $run->notes }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Payslips Tab (unchanged) --}}
        @if ($activeTab === 'payslips')
            <livewire:qf.data-table :configKey="'hr.payroll_payslip'" :queryFilters="['payroll_run_id' => $run->id]" :controls="[
                'search' => true,
                'perPage' => [10, 25, 50, 100],
                'showHideColumns' => true,
                'filterColumns' => true,
            ]" :simpleActions="['expand']"
                :expandConfig="[
                    'component' => 'qf.payslip-items',
                    'params' => [],
                    'label' => 'Items',
                    'icon' => 'fas fa-list',
                ]" />
        @endif

        {{-- Adjustments Tab (unchanged) --}}
        @if ($activeTab === 'adjustments')
            <livewire:qf.data-table :configKey="'hr.payroll_run_adjustment'" :queryFilters="['payroll_run_id' => $run->id]" :controls="[
                'search' => true,
                'perPage' => [10, 25, 50, 100],
                'bulkActions' => [
                    'delete' => true,
                    'export' => ['csv', 'pdf'],
                ],
                'showHideColumns' => true,
                'filterColumns' => true,
            ]" />
        @endif

        {{-- Reconciliation Tab --}}
        @if ($activeTab === 'reconciliation')
            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                            <h5 class="fw-bold text-primary mb-0">Bank Reconciliation</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row gy-3">
                                <div class="col-sm-3 text-muted fw-semibold small text-uppercase">Reconciliation Status
                                </div>
                                <div class="col-sm-9 text-dark fw-medium">
                                    @if ($run->reconciliation_status === 'reconciled')
                                        <span class="badge bg-success">Reconciled</span>
                                    @elseif ($run->reconciliation_status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </div>

                                @if ($run->payment_batch_id)
                                    <div class="col-sm-3 text-muted fw-semibold small text-uppercase">Payment Batch ID
                                    </div>
                                    <div class="col-sm-9 text-dark fw-medium">
                                        {{ $run->payment_batch_id }}
                                    </div>
                                @endif

                                @if ($run->reconciled_at)
                                    <div class="col-sm-3 text-muted fw-semibold small text-uppercase">Reconciled At
                                    </div>
                                    <div class="col-sm-9 text-dark fw-medium">
                                        {{ $run->reconciled_at->format('M d, Y H:i:s') }}
                                    </div>
                                @endif

                                <div class="col-12 mt-3">
                                    @if ($run->status === 'paid' && $run->reconciliation_status !== 'reconciled')
                                        <button wire:click="markAsReconciled" class="btn btn-primary">
                                            <i class="fas fa-check-double"></i> Mark as Reconciled
                                        </button>
                                    @endif
                                    @if ($run->reconciliation_status === 'reconciled')
                                        <span class="text-success"><i class="fas fa-check-circle"></i> This run has
                                            been reconciled.</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Audit Tab (improved) --}}
        @if ($activeTab === 'audit')
            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                            <h5 class="fw-bold text-primary mb-0">Creation & Updates</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row gy-3">
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Created By</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->created_by ?? 'System' }}
                                </div>
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Created At</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->created_at->format('M d, Y H:i:s') }}
                                </div>
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Updated By</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->updated_by ?? '—' }}
                                </div>
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Updated At</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->updated_at->format('M d, Y H:i:s') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                            <h5 class="fw-bold text-primary mb-0">Processing & Approval</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row gy-3">
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Processed By</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->processed_by ?? '—' }}
                                </div>
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Processed At</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->processed_at ? $run->processed_at->format('M d, Y H:i:s') : '—' }}
                                </div>
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Approved By</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->approvedByUser?->name ?? ($run->approved_by ?? '—') }}
                                </div>
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Approved At</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->approved_at ? $run->approved_at->format('M d, Y H:i:s') : '—' }}
                                </div>
                                @if ($run->reconciled_at)
                                    <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Reconciled At
                                    </div>
                                    <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                        {{ $run->reconciled_at->format('M d, Y H:i:s') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
