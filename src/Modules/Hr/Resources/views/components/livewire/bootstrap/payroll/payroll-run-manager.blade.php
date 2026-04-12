{{-- Preview Payroll Modal --}}
{{--<div
    x-data="{ open: @entangle('showPreviewModal') }"
    x-show="open"
    x-on:keydown.escape.window="open = false"
    class="modal fade show d-block"
    style="background-color: rgba(0,0,0,0.5); display: none;"
    x-cloak
> --}}
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">
                    Preview Payroll: {{ $run->paySchedule->name ?? '' }}
                </h5>
                <button type="button" class="btn-close" wire:click="closePreview"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                @if($run)
                    <!-- Summary Banner -->
                    <div class="alert
                        {{ $hasCriticalErrors ? 'alert-danger' : ($warnings->isNotEmpty() ? 'alert-warning' : 'alert-success') }}
                        d-flex align-items-center"
                    >
                        <i class="fas
                            {{ $hasCriticalErrors ? 'fa-exclamation-triangle' : ($warnings->isNotEmpty() ? 'fa-exclamation-circle' : 'fa-check-circle') }}
                            me-2
                        "></i>
                        <div>
                            <strong>{{ $employees->count() }} employees</strong> •
                            Total Payroll: <strong>${{ number_format($totalPayroll, 2) }}</strong>
                            @if($hasCriticalErrors)
                                <br>❌ {{ $criticalErrorCount }} employees missing pay rate — payroll cannot be processed
                            @elseif($warnings->isNotEmpty())
                                <br>⚠️ {{ $warnings->count() }} issue(s) need attention
                            @else
                                <br>✅ All employee payment details are complete
                            @endif
                        </div>
                    </div>

                    <!-- Employee Table -->
                    <div class="table-responsive mt-3">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Pay Type</th>
                                    <th>Gross Pay</th>
                                    <th>Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employees as $emp)
                                    <tr>
                                        <td>
                                            <a href="{{ route('hr.employees.show', $emp['employee']->id) }}" class="text-decoration-none">
                                                {{ $emp['employee']->first_name }} {{ $emp['employee']->last_name }}
                                                <small class="text-muted">({{ $emp['employee']->employee_number }})</small>
                                            </a>
                                        </td>
                                        <td>
                                            @if($emp['employee']->employeePosition?->employment_type === 'Hourly')
                                                <span class="badge bg-info">Hourly</span>
                                            @else
                                                <span class="badge bg-primary">Salaried</span>
                                            @endif
                                        </td>
                                        <td>${{ number_format($emp['gross_pay'], 2) }}</td>
                                        <td>${{ number_format($emp['total_deductions'], 2) }}</td>
                                        <td><strong>${{ number_format($emp['net_pay'], 2) }}</strong></td>
                                        <td>
                                            @if($emp['has_critical_issue'])
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> Missing Pay Rate
                                                </span>
                                            @elseif($emp['missing_bank_info'])
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-circle"></i> Missing Bank Info
                                                </span>
                                            @else
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle"></i> Ready
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Warnings Section -->
                    @if($warnings?->isNotEmpty())
                        <div class="mt-4">
                            <h6 class="border-bottom pb-2">Issues to Review</h6>
                            <div class="row">
                                @foreach($warnings as $warning)
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-{{ $warning['type'] === 'critical' ? 'danger' : 'warning' }}">
                                            <div class="card-body p-3">
                                                <div class="d-flex">
                                                    <i class="fas
                                                        {{ $warning['type'] === 'critical' ? 'fa-exclamation-triangle text-danger' : 'fa-exclamation-circle text-warning' }}
                                                        mt-1 me-2
                                                    "></i>
                                                    <div>
                                                        <p class="mb-1">
                                                            <strong>{{ $warning['message'] }}</strong>
                                                        </p>
                                                        <p class="mb-1 text-muted small">{{ $warning['impact'] }}</p>
                                                        @if(isset($warning['fix_url']))
                                                            <a href="{{ $warning['fix_url'] }}" class="btn btn-sm
                                                                {{ $warning['type'] === 'critical' ? 'btn-outline-danger' : 'btn-outline-warning' }}
                                                            ">
                                                                Fix Now
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closePreview">
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-success"
                    wire:click="$set('showApproveConfirm', true)"
                    :disabled="$hasCriticalErrors"
                    {{ $hasCriticalErrors ? 'disabled' : '' }}
                >
                    <i class="fas fa-check-circle me-1"></i> Approve Payroll
                </button>
            </div>
        </div>
    </div>

    {{-- Approval Confirmation Modal --}}
    {{-- <div x-show="@entangle('showApproveConfirm')" class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5); display: none;" x-cloak>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Approval</h5>
                    <button type="button" class="btn-close" wire:click="$set('showApproveConfirm', false)"></button>
                </div>
                <div class="modal-body">
                    <p>
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        <strong>Are you sure you want to approve this payroll run?</strong>
                    </p>
                    @if($warnings?->isNotEmpty())
                        <p class="text-muted">
                            {{ $warnings->count() }} issue(s) need attention, but you can approve anyway.
                        </p>
                    @endif
                    <p class="text-danger small">
                        <i class="fas fa-lock me-1"></i>
                        This will lock all payroll data and generate official payslips.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showApproveConfirm', false)">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-success" wire:click="approvePayroll">
                        Yes, Approve Payroll
                    </button>
                </div>
            </div>
        </div>
    </div> --}}


{{--  </div> --}}

{{-- Alpine.js init --}}

