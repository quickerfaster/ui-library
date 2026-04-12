@props(['field', 'label', 'type', 'options' => [], 'currentValue' => null])

<div class="column-filter-popover p-2" style="min-width: 200px;">
    <div class="mb-2">
        <strong>Filter by {{ $label }}</strong>
    </div>
    
    @switch($type)
        @case('string')
        @case('text')
        @case('textarea')
            <div class="mb-2">
                <input type="text" class="form-control form-control-sm" 
                       placeholder="Contains..." 
                       id="filter-value-{{ $field }}"
                       value="{{ is_string($currentValue) ? $currentValue : '' }}">
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn btn-sm btn-primary apply-filter" data-field="{{ $field }}">
                    Apply
                </button>
                <button class="btn btn-sm btn-link text-muted clear-filter" data-field="{{ $field }}">
                    Clear
                </button>
            </div>
            @break
            
        @case('select')
            <div class="mb-2">
                <select class="form-select form-select-sm" id="filter-value-{{ $field }}">
                    <option value="">All</option>
                    @foreach($options as $val => $label)
                        <option value="{{ $val }}" {{ ($currentValue == $val || (is_array($currentValue) && in_array($val, $currentValue))) ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn btn-sm btn-primary apply-filter" data-field="{{ $field }}">
                    Apply
                </button>
                <button class="btn btn-sm btn-link text-muted clear-filter" data-field="{{ $field }}">
                    Clear
                </button>
            </div>
            @break
            
        @case('boolean')
            <div class="mb-2">
                <select class="form-select form-select-sm" id="filter-value-{{ $field }}">
                    <option value="">All</option>
                    <option value="1" {{ $currentValue === '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ $currentValue === '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn btn-sm btn-primary apply-filter" data-field="{{ $field }}">
                    Apply
                </button>
                <button class="btn btn-sm btn-link text-muted clear-filter" data-field="{{ $field }}">
                    Clear
                </button>
            </div>
            @break
            
        @case('date')
            <div class="mb-2">
                <input type="date" class="form-control form-control-sm" 
                       id="filter-value-{{ $field }}"
                       value="{{ is_string($currentValue) ? $currentValue : '' }}">
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn btn-sm btn-primary apply-filter" data-field="{{ $field }}">
                    Apply
                </button>
                <button class="btn btn-sm btn-link text-muted clear-filter" data-field="{{ $field }}">
                    Clear
                </button>
            </div>
            @break
            
        @case('number')
            <div class="mb-2">
                <input type="number" step="any" class="form-control form-control-sm" 
                       placeholder="Equals" 
                       id="filter-value-{{ $field }}"
                       value="{{ is_numeric($currentValue) ? $currentValue : '' }}">
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn btn-sm btn-primary apply-filter" data-field="{{ $field }}">
                    Apply
                </button>
                <button class="btn btn-sm btn-link text-muted clear-filter" data-field="{{ $field }}">
                    Clear
                </button>
            </div>
            @break
            
        @default
            <div class="mb-2">
                <input type="text" class="form-control form-control-sm" 
                       placeholder="Filter..." 
                       id="filter-value-{{ $field }}"
                       value="{{ is_string($currentValue) ? $currentValue : '' }}">
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn btn-sm btn-primary apply-filter" data-field="{{ $field }}">
                    Apply
                </button>
                <button class="btn btn-sm btn-link text-muted clear-filter" data-field="{{ $field }}">
                    Clear
                </button>
            </div>
    @endswitch
</div>

@push('scripts')
<script>
    (function() {
        let currentOpenPopover = null;

        function hideAllPopovers() {
            document.querySelectorAll('[data-column-filter]').forEach(el => {
                if (el._popoverInstance) {
                    el._popoverInstance.hide();
                }
            });
            currentOpenPopover = null;
        }

        function initColumnFilters() {
            document.querySelectorAll('[data-column-filter]').forEach(trigger => {
                if (trigger._popoverInstance) return;

                const field = trigger.dataset.field;
                const contentHtml = trigger.dataset.popoverContent;
                if (!contentHtml) return;

                // Create popover instance
                const popover = new bootstrap.Popover(trigger, {
                    html: true,
                    content: contentHtml,
                    placement: 'bottom',
                    trigger: 'manual',      // manual control so we can handle outside click
                    title: '',
                    sanitize: false
                });

                trigger._popoverInstance = popover;

                // Toggle popover on click
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = popover.tip && popover.tip.classList.contains('show');
                    if (isOpen && currentOpenPopover === popover) {
                        popover.hide();
                        currentOpenPopover = null;
                    } else {
                        hideAllPopovers();
                        popover.show();
                        currentOpenPopover = popover;
                    }
                });

                // When popover is shown, attach button listeners
                trigger.addEventListener('shown.bs.popover', function() {
                    const tip = popover.tip;
                    if (!tip) return;

                    const applyBtn = tip.querySelector('.apply-filter');
                    const clearBtn = tip.querySelector('.clear-filter');
                    const valueInput = tip.querySelector('#filter-value-' + field);

                    if (applyBtn) {
                        applyBtn.onclick = (e) => {
                            e.preventDefault();
                            const value = valueInput ? valueInput.value : '';
                            @this.applyQuickFilter(field, value);
                            popover.hide();
                            currentOpenPopover = null;
                        };
                    }

                    if (clearBtn) {
                        clearBtn.onclick = (e) => {
                            e.preventDefault();
                            @this.clearQuickFilter(field);
                            popover.hide();
                            currentOpenPopover = null;
                        };
                    }
                });
            });
        }

        // Close popover when clicking outside
        document.addEventListener('click', function(e) {
            if (currentOpenPopover) {
                const trigger = currentOpenPopover._element;
                const tip = currentOpenPopover.tip;
                if (!trigger.contains(e.target) && (!tip || !tip.contains(e.target))) {
                    currentOpenPopover.hide();
                    currentOpenPopover = null;
                }
            }
        });

        document.addEventListener('livewire:init', initColumnFilters);
        document.addEventListener('livewire:updated', initColumnFilters);
    })();
</script>
@endpush