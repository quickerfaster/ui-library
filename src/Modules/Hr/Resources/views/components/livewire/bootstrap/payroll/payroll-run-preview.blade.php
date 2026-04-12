<div wire:ignore.self id="payrollRunPreview" class="modal-wrapper">
    <!-- Modal Backdrop -->
    <div class="modal-backdrop" id="modalBackdrop"
         onclick="Livewire.dispatch('close-modal-event', [{'modalId': 'payrollRunPreview'}])">
    </div>

    <!-- Modal Content -->
    <div class="modal-content py-4 px-5" style="width: 95%;">
        <!-- Modal Header -->
        <h5 class="card-title text-info text-gradient font-weight-bolder p-4 mx-4 mt-2 mb-2 pb-2">
            Preview Payroll: {{ $run?->paySchedule?->name ?? 'Run' }}
        </h5>
        <div class="mb-4">
            <hr class="horizontal dark my-0" />
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            <!-- Summary Banner -->
            <div class="alert
                {{ $hasCriticalErrors ? 'alert-danger' : (count($warnings) ? 'alert-warning' : 'alert-success') }}
                d-flex align-items-center mb-4"
            >
                <i class="fas
                    {{ $hasCriticalErrors ? 'fa-exclamation-triangle' : (count($warnings) ? 'fa-exclamation-circle' : 'fa-check-circle') }}
                    me-2
                "></i>
                <div>
                    <strong>{{ count($employees) }} employees</strong> •
                    Total Payroll: <strong>${{ number_format($totalPayroll, 2) }}</strong>
                    @if($hasCriticalErrors)
                        <br>❌ Critical errors — payroll cannot be processed
                    @elseif(count($warnings))
                        <br>⚠️ {{ count($warnings) }} issue(s) need attention
                    @else
                        <br>✅ All employee payment details are complete
                    @endif
                </div>
            </div>

            <!-- Employee Table -->
            <div class="table-responsive mb-4">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Pay Type</th>
                            <th>Gross Pay</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $emp)
                            <tr>
                                <td>
                                    {{ $emp['employee']->first_name }} {{ $emp['employee']->last_name }}
                                    <small class="text-muted">({{ $emp['employee']->employee_number }})</small>
                                </td>
                                <td>
                                    @if($emp['employee']->employeePosition?->employment_type === 'Hourly')
                                        <span class="badge bg-info">Hourly</span>
                                    @else
                                        <span class="badge bg-primary">Salaried</span>
                                    @endif
                                </td>
                                <td>${{ number_format($emp['gross_pay'], 2) }}</td>
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
<!-- Warnings Section -->
@if(count($warnings))
    <div class="mb-4">
        <h6 class="border-bottom pb-2">Issues to Review</h6>
        <div class="row">
            @foreach($warnings as $index => $warning)
                <div class="col-md-6 mb-3">
                    <div class="card border-{{ $warning['type'] === 'critical' ? 'danger' : 'warning' }}">
                        <div class="card-body p-3">
                            <div class="d-flex">
                                <i class="fas
                                    {{ $warning['type'] === 'critical' ? 'fa-exclamation-triangle text-danger' : 'fa-exclamation-circle text-warning' }}
                                    mt-1 me-2
                                "></i>
                                <div>
                                    <p class="mb-1"><strong>{{ $warning['message'] }}</strong></p>
                                    <p class="mb-2 text-muted small">{{ $warning['impact'] }}</p>

                                    <!-- Fix button: use first matching individual warning -->
                                    @php
                                        $firstMatch = collect($individualWarnings)->first(function ($item) use ($warning) {
                                            return str_contains($warning['message'], $item['employee_name'] ?? '')
                                                || (count($individualWarnings) == count($warnings) && $loop->index == $index);
                                        });
                                    @endphp

                                    @if($firstMatch)
                                        <button type="button"
                                                class="btn btn-sm {{ $warning['type'] === 'critical' ? 'btn-outline-danger' : 'btn-outline-warning' }}"
                                                wire:click="fixRecord(
                                                    {{ $firstMatch['employee_payroll_profile_id'] ?? $firstMatch['employee_position_id'] }},
                                                    '{{ addslashes($firstMatch['fix_model']) }}'
                                                )">
                                            Fix Now
                                        </button>
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




        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary"
                wire:click="$dispatch('close-modal-event', {'modalId': 'payrollRunPreview'})">
                Cancel
            </button>
            <button type="button" class="btn btn-success"
                wire:click="approvePayroll"
                @if($hasCriticalErrors) disabled @endif>
                <i class="fas fa-check-circle me-1"></i> Approve Payroll
            </button>
        </div>
    </div>
</div>
