<div>
    <!-- Header -->
    <div class="container" style="width: 850px">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Adjust Attendance</h1>
                <p class="text-muted mb-0">
                    {{ $attendance->employee->first_name }} {{ $attendance->employee->last_name }} •
                    {{ $attendance->date->format('M d, Y') }}
                </p>
            </div>

            <a href="/hr/attendances" class="btn bg-gradient-secondary btn-sm my-0">
                <i class="bi bi-arrow-left"></i> &larr; Go Back
            </a>
        </div>

        <!-- Flash Message -->
        @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Form -->
        <div class="card p-4">
            <h5 class="mb-4">What needs to be changed?</h5>

            <!-- Current Values (Read-only) -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted">CURRENT VALUES</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Hours Worked</label>
                        <div class="form-control bg-light">{{ $original_net_hours }} hours</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <div>
                            <span class="badge bg-secondary px-3 py-2 text-capitalize">
                                {{ $original_status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Values (Editable) -->
            <form wire:submit.prevent="save">
                <h6 class="mb-3 text-primary">NEW VALUES</h6>

                <!-- Hours Input -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="adjusted_net_hours" class="form-label fw-medium">Hours Worked</label>
                        <input type="number" class="form-control @error('adjusted_net_hours') is-invalid @enderror"
                            id="adjusted_net_hours" wire:model="adjusted_net_hours" step="0.25" min="0"
                            max="24">
                        @error('adjusted_net_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Enter new total hours (0-24)</div>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="col-md-6">
                        <label for="adjusted_status" class="form-label fw-medium">Status</label>
                        <select class="form-select @error('adjusted_status') is-invalid @enderror" id="adjusted_status"
                            wire:model="adjusted_status">
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                            <option value="holiday">Holiday</option>
                            <option value="leave">On Leave</option>
                        </select>
                        @error('adjusted_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Reason (Required) -->
                <div class="mb-4">
                    <label for="reason" class="form-label fw-medium">
                        Reason for change <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" rows="3" wire:model="reason"
                        placeholder="Why are you making this change?"></textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">This will be saved in the audit trail</div>
                </div>

                <!-- Simple Audit Note -->
                <div class="alert alert-light mb-4">
                    <small>
                        <i class="bi bi-info-circle"></i>
                        This change will be logged as: <br>
                        <strong>"Adjusted by {{ auth()->user()->name }} on {{ now()->format('M d, Y H:i') }}"</strong>
                    </small>
                </div>

                <!-- Submit Buttons -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">

                    <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                        <span wire:loading.remove>Save Changes</span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Changes (MVP audit trail) -->
        <div class="mt-4">
            <h6 class="mb-3">Recent Changes</h6>
            <div class="list-group">
                @forelse($recentAdjustments as $adjustment)
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <small class="text-muted">{{ $adjustment->adjusted_at->format('M d, Y H:i') }}</small>
                            <small class="text-primary">{{ $adjustment->adjusted_by }}</small>
                        </div>
                        <p class="mb-1">
                            Changed from
                            <strong>{{ $adjustment->original_net_hours }} hrs</strong>
                            to
                            <strong>{{ $adjustment->adjusted_net_hours }} hrs</strong>
                            @if ($adjustment->original_status != $adjustment->adjusted_status)
                                <br>Status: {{ $adjustment->original_status }} → {{ $adjustment->adjusted_status }}
                            @endif
                        </p>
                        <small class="text-muted">Reason: {{ $adjustment->reason }}</small>
                    </div>
                @empty
                    <div class="list-group-item text-muted">
                        No adjustments yet
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
