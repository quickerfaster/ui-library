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
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Financial Totals Card --}}
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
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Total Gross Pay</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $currencySymbol }}{{ number_format($run->total_gross_pay, 2) }}
                                </div>

                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Total Deductions</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $currencySymbol }}{{ number_format($run->total_deductions, 2) }}
                                </div>

                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Total Taxes</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $currencySymbol }}{{ number_format($run->total_taxes, 2) }}
                                </div>

                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Net Cash Required
                                </div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    <strong>{{ $currencySymbol }}{{ number_format($run->total_cash_required, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notes Card (if any) --}}
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

        {{-- Payslips Tab --}}
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

        {{-- Adjustments Tab (read-only) --}}
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

        {{-- Audit Tab --}}
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
                                    {{ $run->approved_by ?? '—' }}
                                </div>
                                <div class="col-sm-4 text-muted fw-semibold small text-uppercase">Approved At</div>
                                <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                    {{ $run->approved_at ? $run->approved_at->format('M d, Y H:i:s') : '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
