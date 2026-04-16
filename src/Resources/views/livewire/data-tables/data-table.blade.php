<div>

    {{-- <livewire:qf.filter-panel :configKey="$configKey" wire:key="filters-{{ $configKey }}" /> --}}

    @php
        $fieldDefinitions = $this->getConfigResolver()->getFieldDefinitions();
        $activeFilters = $this->activeFilters ?? [];

        $activeFiltersList = collect($activeFilters)
            ->map(function ($filter, $index) use ($fieldDefinitions) {
                $def = $fieldDefinitions[$filter['field']] ?? [];
                $label = $def['label'] ?? ucfirst($filter['field']);
                $displayValue = $filter['value'];
                if ($filter['type'] === 'select' && isset($def['options'])) {
                    if (is_array($displayValue)) {
                        $displayValue = array_map(fn($v) => $def['options'][$v] ?? $v, $displayValue);
                    } else {
                        $displayValue = $def['options'][$displayValue] ?? $displayValue;
                    }
                }
                return ['field' => $filter['field'], 'label' => $label, 'displayValue' => $displayValue];
            })
            ->toArray();
    @endphp

    @if (count($activeFiltersList))
        <hr />
        <x-qf::active-filters :filters="$activeFiltersList" />
        <hr />
    @endif



    @if (count($quickFilterValues))
        <button wire:click="clearAllQuickFilters" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-filter-slash"></i> Clear Quick Filters
        </button>
    @endif



    <!-- Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-3 gap-3">

        <!-- Left Side: Actions & Search -->
        <div class="d-flex align-items-center gap-2 flex-grow-1">

            <!-- Action Buttons Group -->
            <div class="d-flex align-items-center gap-2">
                @if (!empty($filesActions['export']))
                    <div class="dropdown d-flex align-items-center">
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 m-0"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download"></i> <span>Export</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-start">
                            @foreach ($filesActions['export'] as $format)
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="#"
                                        wire:click.prevent="exportAll('{{ $format }}')">
                                        <i
                                            class="fas fa-file-{{ $format === 'pdf' ? 'pdf' : ($format === 'csv' ? 'csv' : 'excel') }} me-2"></i>
                                        {{ strtoupper($format) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($filesActions['import']))
                    <button wire:click="import"
                        class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 m-0">
                        <i class="fas fa-upload"></i> <span>Import</span>
                    </button>
                @endif

                @if (!empty($filesActions['print']) && $filesActions['print'] === true)
                    <button wire:click="print"
                        class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 m-0">
                        <i class="fas fa-print"></i> <span>Print</span>
                    </button>
                @endif


                <!-- Filter button -->
                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 m-0"
                    wire:click="openFilterDrawer">
                    <i class="fas fa-filter"></i> Filter
                    @if (count($activeFilters) > 0)
                        <span class="badge bg-primary ms-1">{{ count($activeFilters) }}</span>
                    @endif
                </button>



            </div>

            <!-- Search and PerPage -->
            <div class="d-flex align-items-center gap-2">
                @if ($controls['search'] ?? false)
                    <div class="d-flex align-items-center">
                        <input type="text" wire:model.live.debounce.300ms="search"
                            class="form-control form-control-sm m-0" placeholder="Search..."
                            style="min-width: 200px; height: 31px;">
                    </div>
                @endif

                @if (($controls['perPage'] ?? false) && count($controls['perPage']) > 1)
                    <div class="d-flex align-items-center">
                        <select wire:model.live="perPage" class="form-select form-select-sm w-auto m-0"
                            style="height: 31px;">
                            @foreach ($controls['perPage'] as $value)
                                <option value="{{ $value }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>



        </div>

        <!-- Right Side: Create & View Switches -->
        <div class="d-flex align-items-center gap-2">
            @if (in_array('create', $simpleActions))
                <button wire:click="add" class="btn btn-sm btn-primary d-flex align-items-center gap-2">
                    <i class="fas fa-plus"></i> Add
                </button>
            @endif

            @if (count($switchViews ?? []) >= 2)
                <button wire:click="toggleViewMode"
                    class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 m-0">
                    @if ($viewMode === 'table')
                        <i class="fas fa-list"></i> <span>List</span>
                    @elseif($viewMode === 'list')
                        <i class="fas fa-th-large"></i> <span>Card</span>
                    @else
                        <i class="fas fa-table"></i> <span>Table</span>
                    @endif
                </button>
            @endif

            @if ($this->showHideColumnsEnabled())
                <div class="dropdown me-4" wire:key="column-dropdown-{{ $configKey }}">
                    <button type="button"
                        class="btn dropdown-togglebtn btn-sm btn-outline-secondary d-flex align-items-center gap-2 m-0 gap-2"
                        wire:click="toggleColumnDropdown">
                        <i class="fas fa-columns"></i> Columns
                    </button>

                    @if ($columnDropdownOpen)
                        <ul class="dropdown-menu dropdown-menu-end p-2 show" wire:click.away="closeColumnDropdown">
                            @foreach ($allColumns as $column)
                                @php $def = $columns[$column]; @endphp
                                <li wire:key="col-{{ $column }}-{{ count($visibleColumns) }}"
                                    class="dropdown-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            wire:click="toggleColumn('{{ $column }}')"
                                            id="col-{{ $column }}" @checked(in_array($column, $visibleColumns))>
                                        <label class="form-check-label" for="col-{{ $column }}">
                                            {{ $def['label'] ?? ucfirst($column) }}
                                        </label>
                                    </div>
                                </li>
                            @endforeach

                            @if (count($allColumns) > count($visibleColumns))
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li class="dropdown-item">
                                    <button class="dropdown-item btn-sm d-flex align-items-center"
                                        wire:click="resetColumns">
                                        <i class="fas fa-undo-alt me-2"></i> Reset
                                    </button>
                                </li>
                            @endif
                        </ul>
                    @endif
                </div>
            @endif




            @if ($this->usesSoftDeletes() && ($controls['trashView'] ?? false))
                <div class="d-flex align-items-center">
                    <select wire:model.live="trashedFilter" class="form-select form-select-sm w-auto"
                        style="height: 31px;">
                        <option value="without">Active</option>
                        <option value="with">With Trashed</option>
                        <option value="only">Trashed Only</option>
                    </select>
                </div>
            @endif


        </div>
    </div>

    <!-- Bulk Actions Bar -->
    @if (!empty($bulkSelection['ids']) && !empty($bulkActions))
        <div class="alert alert-primary bg-gradient-primary d-flex justify-content-between align-items-center py-2 px-3 mb-4"
            role="alert">
            <div class="d-flex align-items-center">
                <span class="alert-icon text-white me-3">
                    <i class="fas fa-check-double fa-lg"></i>
                </span>
                <span class="alert-text text-white">
                    <strong style="font-size: 2em">{{ count($bulkSelection['ids']) }}</strong> <strong>items selected
                        for bulk actions</strong>
                </span>
            </div>

            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-white mb-0 dropdown-toggle d-flex align-items-center"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bolt me-2"></i> Bulk Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                    @foreach ($bulkActions as $key => $action)
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#"
                                wire:click.prevent="handleBulkAction('{{ $key }}')">
                                @if (!empty($action['icon']))
                                    <i class="{{ $action['icon'] }} me-2 opacity-6"></i>
                                @endif
                                {{ $action['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Table View --}}
    @if ($viewMode === 'table')
        <div class="table-responsive">
            <table class="table align-items-center mb-0 table-striped">
                <thead>
                    <tr>
                        @if (!empty($controls['bulkActions']))
                            <th class="ps-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input"
                                        wire:model.live="bulkSelection.all">
                                </div>
                            </th>
                        @endif

                        @foreach ($visibleColumns as $name)
                            @php
                                $def = $columns[$name];
                                $isSorted = $sort['field'] === $name;
                                $isRelationship = isset($def['relationship']);
                                $sortClass = $isSorted ? ' sorting sorting-' . $sort['direction'] : '';
                                $filterType = $this->mapFieldTypeToFilterType($def['field_type'] ?? 'string');
                                $currentFilterValue = $this->quickFilterValues[$name] ?? null;
                                $hasActiveFilter = !empty($currentFilterValue);
                            @endphp
                            <th wire:click="{{ $isRelationship ? '' : 'sortBy(\'' . $name . '\')' }}"
                                style="cursor: {{ $isRelationship ? 'default' : 'pointer' }};"
                                class="{{ $sortClass }} ps-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span>{{ $def['label'] ?? ucfirst($name) }}</span>
                                    @if (!$isRelationship && ($controls['filterColumns'] ?? true))
                                        <i class="fas fa-filter ms-1 {{ $hasActiveFilter ? 'text-primary' : 'text-muted' }}"
                                            style="font-size: 0.75rem; cursor: pointer;" data-column-filter
                                            data-field="{{ $name }}"
                                            data-popover-content='@include('qf::components.column-filter', [
                                                'field' => $name,
                                                'label' => $def['label'] ?? ucfirst($name),
                                                'type' => $filterType,
                                                'options' =>
                                                    $filterType === 'select' && isset($def['options'])
                                                        ? $def['options']
                                                        : [],
                                                'currentValue' => $currentFilterValue,
                                            ])' wire:ignore
                                            onclick="event.stopPropagation();"></i>
                                    @endif
                                </div>
                                @if ($isSorted)
                                    <i class="fas fa-sort-{{ $sort['direction'] === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-muted" style="opacity: 0.3;"></i>
                                @endif
                            </th>
                        @endforeach

                        @if (!empty($simpleActions) || !empty($moreActions))
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        @php $isTrashed = $this->usesSoftDeletes() && $this->isTrashed($record); @endphp

                        <tr wire:key="row-{{ $record->id }}">
                            @if (!empty($controls['bulkActions']))
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input"
                                            wire:model.live="bulkSelection.ids" value="{{ $record->id }}">
                                    </div>
                                </td>
                            @endif
                            @foreach ($visibleColumns as $name)
                                @php
                                    $def = $columns[$name];
                                    $field = $this->getField($name, $def);
                                @endphp
                                <td
                                    @if ($isTrashed) class="text-muted bg-light text-decoration-line-through" style="opacity: 0.4;" @endif>
                                    {!! $field->renderTable($record->$name, $record) !!}
                                </td>
                            @endforeach
                            <td class="text-nowrap">
                                @include('qf::livewire.data-tables.partials.row-actions', [
                                    'record' => $record,
                                    'simpleActions' => $simpleActions,
                                    'moreActions' => $moreActions,
                                    'controls' => $controls,
                                    'bulkSelection' => $bulkSelection,
                                    'viewType' => $viewType,
                                    'configKey' => $configKey,
                                    'modelName' => $modelName,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100%">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- List View --}}
    @elseif($viewMode === 'list')
        @include('qf::livewire.data-tables.partials.list-view', [
            'records' => $records,
            'viewConfig' => $viewConfig,
            'simpleActions' => $simpleActions,
            'moreActions' => $moreActions,
            'controls' => $controls,
            'bulkSelection' => $bulkSelection,
            'configKey' => $configKey,
        ])

        {{-- Card View --}}
    @elseif($viewMode === 'card')
        @include('qf::livewire.data-tables.partials.card-view', [
            'records' => $records,
            'viewConfig' => $viewConfig,
            'simpleActions' => $simpleActions,
            'moreActions' => $moreActions,
            'controls' => $controls,
            'bulkSelection' => $bulkSelection,
            'configKey' => $configKey,
        ])
    @endif

    <!-- Pagination -->
    <div class="mt-3">
        {{ $records->links() }}
    </div>

    <style>
        th.sorting.sorting-asc,
        th.sorting.sorting-desc {
            /* background-color: rgba(0, 123, 255, 0.1); */
        }
    </style>

</div>







@push('scripts')
    <script>
        window.addEventListener('open-url-new-tab', event => {
            window.open(event.detail[0], '_blank');
        });
    </script>
@endpush
