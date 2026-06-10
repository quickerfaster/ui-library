<div>
    @if ($policyType === 'tax')
        <div class="mb-3">
            <label class="form-label fw-bold">Tax Brackets (annual income)</label>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Start (₦)</th>
                        <th>End (₦)</th>
                        <th>Rate (%)</th>
                        <th style="width: 50px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bands as $index => $band)
                        <tr wire:key="band-{{ $index }}">
                            <td>
                                <input type="number" step="any" class="form-control form-control-sm"
                                    wire:model.blur="bands.{{ $index }}.start" placeholder="e.g., 0">
                            </td>
                            <td>
                                <input type="number" step="any" class="form-control form-control-sm"
                                    wire:model.blur="bands.{{ $index }}.end"
                                    placeholder="e.g., 800000 (leave blank for infinity)">
                            </td>
                            <td>
                                <input type="number" step="any" class="form-control form-control-sm"
                                    wire:model.blur="bands.{{ $index }}.rate" placeholder="e.g., 15">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    wire:click="removeBand({{ $index }})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                @if (isset($bandErrors[$index]))
                                    <div class="text-danger small mt-1 mb-3">{{ $bandErrors[$index] }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-secondary" wire:click="addBand">
                <i class="fas fa-plus"></i> Add Bracket
            </button>
            <small class="text-muted d-block mt-1">
                Define each bracket by its income range. Leave "End" blank for the highest bracket. Brackets are applied
                in order; they should be contiguous and non‑overlapping.
            </small>
        </div>
    @else
        {{-- Non‑tax UI (unchanged from previous answer) --}}
        <div class="mb-3">
            <label class="form-label fw-bold">Calculation Type</label>
            <div class="mb-2">
                <div class="form-check form-check-inline">
                    <input type="radio" id="fixed" value="fixed" wire:model.live="calculationType"
                        class="form-check-input">
                    <label class="form-check-label" for="fixed">Fixed amount (per pay period)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" id="percentage" value="percentage" wire:model.live="calculationType"
                        class="form-check-input">
                    <label class="form-check-label" for="percentage">Percentage of salary</label>
                </div>
            </div>

            <div class="row">
                @if ($this->showEmployeeField())
                    <div class="col-md-6">
                        <label class="form-label">Employee Contribution</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                @if ($calculationType === 'fixed')
                                    <i class="fas fa-dollar-sign"></i>
                                @else
                                    <i class="fas fa-percent"></i>
                                @endif
                            </span>
                            <input type="number" step="any" class="form-control"
                                wire:model.live.debounce.1000ms="employeeValue" placeholder="0.00">
                        </div>
                        <small class="text-muted">The amount deducted from employee's pay.</small>
                    </div>
                @else
                    <input type="hidden" wire:model="employeeValue" value="0">
                @endif

                @if ($this->showEmployerField())
                    <div class="col-md-6">
                        <label class="form-label">Employer Contribution</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                @if ($calculationType === 'fixed')
                                    <i class="fas fa-dollar-sign"></i>
                                @else
                                    <i class="fas fa-percent"></i>
                                @endif
                            </span>
                            <input type="number" step="any" class="form-control"
                                wire:model.live.debounce.1000ms="employerValue" placeholder="0.00">
                        </div>
                        <small class="text-muted">The amount paid by the employer (informational).</small>
                    </div>
                @else
                    <input type="hidden" wire:model="employerValue" value="0">
                @endif
            </div>
        </div>
    @endif
</div>
