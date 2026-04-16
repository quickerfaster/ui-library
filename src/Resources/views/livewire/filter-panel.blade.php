<div>
    <div class="filter-panel card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filters</h5>
            @if (!empty($filtersConfig))

            <div class="btn-group">
    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fas fa-save"></i> Saved Filters
    </button>
    <ul class="dropdown-menu">
        @foreach ($savedFilters as $saved)
            <li>
                <div class="dropdown-item d-flex justify-content-between align-items-center">
                    <span wire:click.prevent="loadSavedFilter({{ $saved['id'] }})" style="cursor: pointer; flex-grow: 1;">
                        {{ $saved['name'] }}
                        @if ($saved['is_global'] ?? false)
                            <span class="badge bg-info ms-2">Global</span>
                        @endif
                    </span>
                    <div>
                        <button wire:click.prevent="showSaveFilterModal({{ $saved['id'] }})" class="btn btn-sm btn-link" title="Rename">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button wire:click.prevent="confirmDeleteSavedFilter({{ $saved['id'] }})" class="btn btn-sm btn-link text-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </li>
        @endforeach
        @if (count($savedFilters) > 0)
            <li><hr class="dropdown-divider"></li>
        @endif
        <li>
            <a class="dropdown-item" href="#" wire:click.prevent="showSaveFilterModal">
                <i class="fas fa-plus"></i> Save current filters...
            </a>
        </li>
    </ul>
</div>
            @endif
        </div>

        <div class="card-body">
            @if (empty($filtersConfig))
                <p class="text-muted">No filters configured for this module.</p>
            @else
                <div class="row g-3">
                    @foreach ($filtersConfig as $index => $filter)
                        <div class="col-12 ">
                            <label class="form-label">{{ $filter['label'] }}</label>
                            <div class="d-flex gap-2">
                                {{-- Operator dropdown --}}
                                @if (count($filter['operators']) > 1)
                                    <select wire:model.live="activeFilters.{{ $index }}.operator"
                                        class="form-select w-auto" style="min-width: 100px;">
                                        @foreach ($filter['operators'] as $opKey => $opLabel)
                                            <option value="{{ $opKey }}">{{ $opLabel }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="hidden" wire:model="activeFilters.{{ $index }}.operator"
                                        value="{{ array_key_first($filter['operators']) }}">
                                @endif

                                @php $operator = $activeFilters[$index]['operator'] ?? $filter['defaultOperator']; @endphp

                                {{-- Value input(s) based on type and operator --}}
                                @switch($filter['type'])
                                    @case('select')
                                        @if ($filter['multi'] ?? false)
                                            <select wire:model.live="activeFilters.{{ $index }}.value"
                                                class="form-select" multiple size="3">
                                                @foreach ($filter['options'] ?? [] as $optValue => $optLabel)
                                                    <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                                        @else
                                            <select wire:model.live="activeFilters.{{ $index }}.value"
                                                class="form-select">
                                                <option value="">All</option>
                                                @foreach ($filter['options'] ?? [] as $optValue => $optLabel)
                                                    <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    @break

                                    @case('boolean')
                                        <select wire:model.live="activeFilters.{{ $index }}.value" class="form-select">
                                            <option value="">All</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    @break

                                    @case('date')
                                        @if ($operator === 'between')
                                            <div class="input-group">
                                                <input type="date"
                                                    wire:model.live="activeFilters.{{ $index }}.value.start"
                                                    class="form-control" placeholder="Start">
                                                <span class="input-group-text">to</span>
                                                <input type="date"
                                                    wire:model.live="activeFilters.{{ $index }}.value.end"
                                                    class="form-control" placeholder="End">
                                            </div>
                                        @elseif(in_array($operator, [
                                                'today',
                                                'this_week',
                                                'this_month',
                                                'this_year',
                                                'last_week',
                                                'last_month',
                                                'last_year',
                                                'last_7_days',
                                                'next_30_days',
                                                'this_quarter',
                                                'last_quarter',
                                            ]))
                                            <span
                                                class="form-control-plaintext">{{ $filter['operators'][$operator] ?? ucfirst($operator) }}</span>
                                        @else
                                            <input type="date" wire:model.live="activeFilters.{{ $index }}.value"
                                                class="form-control">
                                        @endif
                                    @break

                                    @case('number')
                                        @if ($operator === 'between')
                                            <div class="input-group">
                                                <input type="number" step="any"
                                                    wire:model.live="activeFilters.{{ $index }}.value.min"
                                                    class="form-control" placeholder="Min">
                                                <span class="input-group-text">to</span>
                                                <input type="number" step="any"
                                                    wire:model.live="activeFilters.{{ $index }}.value.max"
                                                    class="form-control" placeholder="Max">
                                            </div>
                                        @else
                                            <input type="number" step="any"
                                                wire:model.live="activeFilters.{{ $index }}.value" class="form-control">
                                        @endif
                                    @break

                                    @default
                                        <input type="text"
                                            wire:model.live.debounce.300ms="activeFilters.{{ $index }}.value"
                                            class="form-control">
                                @endswitch
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    <button wire:click="clearFilters" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Save Filter Modal --}}
    <div class="modal fade" id="saveFilterModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Save Filter Set</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Filter Name</label>
                        <input type="text" class="form-control" wire:model="filterName"
                            placeholder="e.g., Pending approvals December">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" wire:model="filterIsGlobal"
                            id="globalFilter">
                        <label class="form-check-label" for="globalFilter">
                            Make available to all users (admin only)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="saveFilter">Save</button>
                </div>
            </div>
        </div>
    </div>



</div>
