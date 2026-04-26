<div>
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3>Payroll Run #{{ $run->id }}</h3>
            <p class="text-muted">
                {{ $run->paySchedule->name }} |
                {{ \Carbon\Carbon::parse($run->period_start)->format('M d, Y') }} –
                {{ \Carbon\Carbon::parse($run->period_end)->format('M d, Y') }}
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
            | Approved by {{ $run->approved_by }} on
            {{ \Carbon\Carbon::parse($run->approved_at)->format('M d, Y H:i') }}
        @endif
        @if ($run->processed_at)
            | Paid on {{ \Carbon\Carbon::parse($run->processed_at)->format('M d, Y H:i') }}
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
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">Period Details</div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Pay Schedule</dt>
                                <dd class="col-sm-8">{{ $run->paySchedule->name }}</dd>
                                <dt class="col-sm-4">Period Start</dt>
                                <dd class="col-sm-8">{{ \Carbon\Carbon::parse($run->period_start)->format('M d, Y') }}
                                </dd>
                                <dt class="col-sm-4">Period End</dt>
                                <dd class="col-sm-8">{{ \Carbon\Carbon::parse($run->period_end)->format('M d, Y') }}
                                </dd>
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
                                <dd class="col-sm-6 text-end">
                                    {{ $currencySymbol }}{{ number_format($run->total_gross_pay, 2) }}</dd>
                                <dt class="col-sm-6">Total Deductions</dt>
                                <dd class="col-sm-6 text-end">
                                    {{ $currencySymbol }}{{ number_format($run->total_deductions, 2) }}</dd>
                                <dt class="col-sm-6">Total Taxes</dt>
                                <dd class="col-sm-6 text-end">
                                    {{ $currencySymbol }}{{ number_format($run->total_taxes, 2) }}</dd>
                                <dt class="col-sm-6"><strong>Net Cash Required</strong></dt>
                                <dd class="col-sm-6 text-end">
                                    <strong>{{ $currencySymbol }}{{ number_format($run->total_cash_required, 2) }}</strong>
                                </dd>
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


        {{-- Payslips Tab --}}
        @if ($activeTab === 'payslips')
            {{-- Filters --}}
            <div class="row mb-3 g-2">
                <div class="col-md-2">
                    <label class="form-label">Company</label>
                    <select wire:model.live="filterCompany" class="form-select form-select-sm">
                        <option value="">All Companies</option>
                        @foreach ($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select wire:model.live="filterDepartment" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <select wire:model.live="filterLocation" class="form-select form-select-sm">
                        <option value="">All Locations</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select wire:model.live="filterEmploymentStatus" class="form-select form-select-sm">
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Terminated">Terminated</option>
                        <option value="All">All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm"
                        placeholder="Name or Employee #">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button wire:click="resetFilters" class="btn btn-sm btn-secondary w-100">
                        <i class="fas fa-undo-alt"></i>
                    </button>
                </div>
            </div>

            {{-- Result count --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>Showing {{ $payslips->total() }} payslips</div>
                @if (!empty($search))
                    <div class="text-muted small">Search results for: <strong>{{ $search }}</strong></div>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payslips as $payslip)
                            @php
                                $position = $payslip->employee->employeePosition;
                                $currencyCode = $position->salary_currency ?? 'USD';
                                $currencySymbol = $this->getCurrencySymbol($currencyCode);
                            @endphp
                            <tr wire:key="payslip-{{ $payslip->id }}">
                                <td>{{ $payslip->employee->first_name }} {{ $payslip->employee->last_name }}
                                    ({{ $payslip->employee->employee_number }})
                                </td>
                                <td>{{ $currencySymbol }}{{ number_format($payslip->gross_pay, 2) }}</td>
                                <td>{{ $currencySymbol }}{{ number_format($payslip->total_deductions, 2) }}</td>
                                <td>{{ $currencySymbol }}{{ number_format($payslip->net_pay, 2) }}</td>
                                <td>
                                    <button wire:click="togglePayslipDetails({{ $payslip->id }})"
                                        class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </td>
                            </tr>
                            @if ($expandedPayslipId === $payslip->id)
                                <tr>
                                    <td colspan="5" class="p-0">
                                        <div class="p-3 bg-light">
                                            @include('hr::partials._payslip_items_table', [
                                                'items' => $lazyItemsCache[$payslip->id] ?? [],
                                                'currencySymbol' => $currencySymbol,
                                            ])
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No payslips found for the selected
                                    filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($payslips->hasPages())
                <div class="mt-3">
                    {{ $payslips->links() }}
                </div>
            @endif
        @endif

        {{-- Adjustments Tab --}}
        @if ($activeTab === 'adjustments')
            <div class="row mb-3 g-2">
                <div class="col-md-2">
                    <label class="form-label">Company</label>
                    <select wire:model.live="adjustmentFilterCompany" class="form-select form-select-sm">
                        <option value="">All Companies</option>
                        @foreach ($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select wire:model.live="adjustmentFilterDepartment" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <select wire:model.live="adjustmentFilterLocation" class="form-select form-select-sm">
                        <option value="">All Locations</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select wire:model.live="adjustmentFilterEmploymentStatus" class="form-select form-select-sm">
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Terminated">Terminated</option>
                        <option value="All">All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="adjustmentSearch"
                        class="form-control form-control-sm" placeholder="Employee name or number">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button wire:click="resetAdjustmentFilters" class="btn btn-sm btn-secondary w-100">
                        <i class="fas fa-undo-alt"></i>
                    </button>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6">
                    <span class="text-muted small">Total adjustments: {{ $adjustments->total() }}</span>
                </div>
                <div class="col-md-6 text-end">
                    <select wire:model.live="adjustmentTypeFilter"
                        class="form-select form-select-sm d-inline-block w-auto">
                        <option value="">All Types</option>
                        <option value="bonus">Bonus</option>
                        <option value="commission">Commission</option>
                        <option value="correction">Correction</option>
                        <option value="reimbursement">Reimbursement</option>
                        <option value="deduction">Deduction</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $adj)
                            @php
                                $position = $adj->employee->employeePosition;
                                $currencyCode = $position->salary_currency ?? 'USD';
                                $currencySymbol = $this->getCurrencySymbol($currencyCode);
                            @endphp
                            <tr wire:key="adj-{{ $adj->id }}">
                                <td>{{ $adj->employee->first_name }} {{ $adj->employee->last_name }}
                                    ({{ $adj->employee->employee_number }})</td>
                                <td>{{ ucfirst($adj->type) }}</td>
                                <td>{{ $adj->label }}</td>
                                <td class="text-end">
                                    @if ($adj->type !== 'deduction')
                                        <span class="text-success">+
                                            {{ $currencySymbol }}{{ number_format($adj->amount, 2) }}</span>
                                    @else
                                        <span class="text-danger">-
                                            {{ $currencySymbol }}{{ number_format($adj->amount, 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No adjustments found for this run.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($adjustments->hasPages())
                <div class="mt-3">
                    {{ $adjustments->links() }}
                </div>
            @endif
        @endif

        {{-- Audit Tab --}}
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
                        <dd class="col-sm-9">
                            {{ $run->processed_at ? \Carbon\Carbon::parse($run->processed_at)->format('M d, Y H:i:s') : '—' }}
                        </dd>
                        <dt class="col-sm-3">Approved By</dt>
                        <dd class="col-sm-9">{{ $run->approved_by ?? '—' }}</dd>
                        <dt class="col-sm-3">Approved At</dt>
                        <dd class="col-sm-9">
                            {{ $run->approved_at ? \Carbon\Carbon::parse($run->approved_at)->format('M d, Y H:i:s') : '—' }}
                        </dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
</div>
