<div>
    @if($policyType === 'tax')
        <div class="mb-3">
            <label class="form-label fw-bold">Tax Brackets (annual limits)</label>
            <livewire:qf.tax-bands-repeater :initialRows="$bands" wire:key="repeater-{{ $policyType }}" />
            <small class="text-muted">Each bracket: limit = upper bound (e.g., 80000), rate = tax % (e.g., 15). Leave limit blank for infinity.</small>
        </div>
    @else
        <div class="mb-3">
            <label class="form-label fw-bold">Calculation Type</label>
            <div class="mb-2">
                <div class="form-check form-check-inline">
                    <input type="radio" id="fixed" value="fixed" wire:model.live="calcType" class="form-check-input">
                    <label class="form-check-label" for="fixed">Fixed amount</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" id="percentage" value="percentage" wire:model.live="calcType" class="form-check-input">
                    <label class="form-check-label" for="percentage">Percentage of salary</label>
                </div>
            </div>
            <div class="input-group">
                <span class="input-group-text">
                    @if($calcType === 'fixed')
                        <i class="fas fa-dollar-sign"></i>
                    @else
                        <i class="fas fa-percent"></i>
                    @endif
                </span>
                <input type="number" step="any" class="form-control" wire:model="calcValue" placeholder="0.00">
            </div>
        </div>
    @endif
</div>