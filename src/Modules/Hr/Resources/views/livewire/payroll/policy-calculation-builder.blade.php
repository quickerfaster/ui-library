<div>
    @if($policyType === 'tax')
        <div class="mb-3">
            <label class="form-label fw-bold">Tax Brackets (annual limits)</label>
            <table class="table table-sm table-borderless">
                <thead>
                    <tr><th>Limit (annual $)</th><th>Rate (%)</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($bands as $index => $band)
                        <tr wire:key="band-{{ $index }}">
                            <td>
                                <input type="number" step="any" class="form-control form-control-sm"
                                       wire:model.blur="bands.{{ $index }}.limit"
                                       placeholder="e.g., 80000">
                            </td>
                            <td>
                                <input type="number" step="any" class="form-control form-control-sm"
                                       wire:model.blur="bands.{{ $index }}.rate"
                                       placeholder="e.g., 15">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="removeBand({{ $index }})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-secondary" wire:click="addBand">
                <i class="fas fa-plus"></i> Add Bracket
            </button>
            <small class="text-muted d-block mt-1">
                Each bracket: limit = upper bound (e.g., 80000), rate = tax % (e.g., 15). Leave limit blank for infinity.
            </small>
        </div>
    @else
        <div class="mb-3">
            <label class="form-label fw-bold">Calculation Type</label>
            <div class="mb-2">
                <div class="form-check form-check-inline">
                    <input type="radio" id="fixed" value="fixed" wire:model.live="calculationType" class="form-check-input">
                    <label class="form-check-label" for="fixed">Fixed amount (per pay period)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" id="percentage" value="percentage" wire:model.live="calculationType" class="form-check-input">
                    <label class="form-check-label" for="percentage">Percentage of salary</label>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Employee Contribution</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            @if($calculationType === 'fixed')
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
                <div class="col-md-6">
                    <label class="form-label">Employer Contribution</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            @if($calculationType === 'fixed')
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
            </div>
        </div>
    @endif
</div>