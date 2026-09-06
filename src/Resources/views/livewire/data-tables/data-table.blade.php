<div>

    @if($pageTitle)
    @script
    <script>
        document.title = @js($pageTitle);
        const heading = document.querySelector('h1.h3.mb-0');
        if (heading) heading.textContent = @js($pageTitle);
    </script>
    @endscript
    @endif

    {{-- <livewire:qf.filter-panel :configKey="$configKey" wire:key="filters-{{ $configKey }}" /> --}}



    @if (count($this->activeFilters))
        <hr />
        <x-qf::active-filters />
        <hr />
    @endif


    {{-- Active Search Info --}}
    <x-qf::active-search :searchTerm="$this->search" :columns="$this->selectedSearchColumns" :exactMatch="$this->exactMatch" :columnsLabels="$this->searchColumnsLabels" />



    {{-- @if (count($quickFilterValues))
        <button wire:click="clearAllQuickFilters" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-filter-slash"></i> Clear Quick Filters
        </button>
    @endif
    --}}




    {{-- NORMAL TOOLBAR (no selection) --}}
    @if (empty($bulkSelection['ids']))
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center">
                {{-- Search + Filter group --}}
                <div class="input-group input-group-sm" style="min-width: 250px;">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="Search..." />
                    <button class="btn btn-outline-secondary" type="button" wire:click="openSearchDrawer"
                        title="Advanced search">
                        <i class="fas fa-sliders-h"></i>
                    </button>
                    <button class="btn btn-outline-secondary" type="button" wire:click="openFilterDrawer"
                        title="Filter">
                        <i class="fas fa-filter"></i>
                        @if (count($activeFilters) > 0)
                            <span class="badge bg-primary ms-1">{{ count($activeFilters) }}</span>
                        @endif
                    </button>
                </div>

                {{-- View Menu --}}

                {{-- View Menu --}}
                <div class="dropdown ms-2">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <ul class="dropdown-menu shadow-sm">
                        {{-- Display section --}}
                        <li>
                            <h6 class="dropdown-header">Display</h6>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#"
                                wire:click.prevent="setViewMode('table')">
                                <span class="{{ $viewMode === 'table' ? 'fw-bold text-primary' : '' }}">
                                    <i class="fas fa-table me-2"></i>Table
                                </span>
                                <i class="fas fa-check {{ $viewMode === 'table' ? 'text-primary' : 'invisible' }}"></i>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#"
                                wire:click.prevent="setViewMode('list')">
                                <span class="{{ $viewMode === 'list' ? 'fw-bold text-primary' : '' }}">
                                    <i class="fas fa-list me-2"></i>List
                                </span>
                                <i class="fas fa-check {{ $viewMode === 'list' ? 'text-primary' : 'invisible' }}"></i>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#"
                                wire:click.prevent="setViewMode('card')">
                                <span class="{{ $viewMode === 'card' ? 'fw-bold text-primary' : '' }}">
                                    <i class="fas fa-th-large me-2"></i>Cards
                                </span>
                                <i class="fas fa-check {{ $viewMode === 'card' ? 'text-primary' : 'invisible' }}"></i>
                            </a>
                        </li>
                        @if (isset($switchViews['monthly']))
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#"
                                wire:click.prevent="setViewMode('monthly')">
                                <span class="{{ $viewMode === 'monthly' ? 'fw-bold text-primary' : '' }}">
                                    <i class="fas fa-calendar-alt me-2"></i>Monthly
                                </span>
                                <i class="fas fa-check {{ $viewMode === 'monthly' ? 'text-primary' : 'invisible' }}"></i>
                            </a>
                        </li>
                        @endif

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        {{-- Density section --}}
                        <li>
                            <h6 class="dropdown-header">Density</h6>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#"
                                wire:click.prevent="setDensity('comfortable')">
                                <span class="{{ $density === 'comfortable' ? 'fw-bold text-primary' : '' }}">
                                    Comfortable
                                </span>
                                <i
                                    class="fas fa-check {{ $density === 'comfortable' ? 'text-primary' : 'invisible' }}"></i>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#"
                                wire:click.prevent="setDensity('compact')">
                                <span class="{{ $density === 'compact' ? 'fw-bold text-primary' : '' }}">
                                    Compact
                                </span>
                                <i
                                    class="fas fa-check {{ $density === 'compact' ? 'text-primary' : 'invisible' }}"></i>
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        {{-- Rows per page section --}}
                        <li>
                            <h6 class="dropdown-header">Rows per page</h6>
                        </li>
                        @foreach ($controls['perPage'] ?? [10, 25, 50, 100] as $value)
                            <li>
                                <a class="dropdown-item d-flex align-items-center justify-content-between"
                                    href="#" wire:click.prevent="setPerPageFromView({{ $value }})">
                                    <span class="{{ $perPage == $value ? 'fw-bold text-primary' : '' }}">
                                        {{ $value }}
                                    </span>
                                    <i
                                        class="fas fa-check {{ $perPage == $value ? 'text-primary' : 'invisible' }}"></i>
                                </a>
                            </li>
                        @endforeach

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        {{-- Extra actions --}}
                        <li>
                            <a class="dropdown-item" href="#" wire:click.prevent="openColumnManager">
                                <i class="fas fa-columns me-2"></i> Columns...
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#"
                                wire:click.prevent="toggleInlineEditing">
                                <span class="{{ $editable ? 'fw-bold text-primary' : '' }}">
                                    <i class="fas fa-pen me-2"></i> Inline Editing
                                </span>
                                <i class="fas fa-check {{ $editable ? 'text-primary' : 'invisible' }}"></i>
                            </a>
                        </li>
                    </ul>
                </div>


                {{-- Tools Menu --}}
                <div class="dropdown ms-2">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                        <i class="fas fa-tools"></i> Tools
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" wire:click.prevent="exportAll('csv')">
                                <i class="fas fa-file-csv me-2"></i> Export as CSV
                            </a></li>
                        <li><a class="dropdown-item" href="#" wire:click.prevent="exportAll('xls')">
                                <i class="fas fa-file-excel me-2"></i> Export as XLS
                            </a></li>
                        <li><a class="dropdown-item" href="#" wire:click.prevent="exportAll('pdf')">
                                <i class="fas fa-file-pdf me-2"></i> Export as PDF
                            </a></li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#"
                                wire:click.prevent="exportTemplate">
                                <i class="fas fa-download me-2"></i> Export Template (for import)
                            </a>
                        </li>

                        <li><a class="dropdown-item" href="#" wire:click.prevent="import">
                                <i class="fas fa-upload me-2"></i> Import
                            </a></li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li><a class="dropdown-item" href="#" wire:click.prevent="print">
                                <i class="fas fa-print me-2"></i> Print
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#" wire:click.prevent="openBackgroundJobsDrawer">
                                <i class="fas fa-history me-2"></i> Background Jobs
                            </a></li>
                    </ul>
                </div>
            </div>

            <div class="d-flex align-items-center">
                {{-- Status filter (renamed) --}}
                {{-- @if ($this->usesSoftDeletes() && ($controls['trashView'] ?? false))
                    <select wire:model.live="trashedFilter" class="form-select form-select-sm me-2">
                        <option value="without">Active</option>
                        <option value="with">With archived</option>
                        <option value="only">Archived only</option>
                    </select>
                @endif --}}

                {{-- Add button --}}
                @if (in_array('create', $simpleActions))
                    @php
                        $canCreate = $this->authService->canCreate(
                            auth()->user(),
                            $this->getConfigResolver()->getModel(),
                        );
                    @endphp
                    @if ($canCreate)
                        <button wire:click="add" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    @endif
                @endif
            </div>
        </div>
    @else
        {{-- SELECTION MODE TOOLBAR --}}
        <div
            class="alert alert-primary bg-gradient-primary d-flex justify-content-between align-items-center py-2 px-3 mb-3">
            <div class="d-flex align-items-center text-white" style="font-size: 1.5em; font-weight: bold">
                <i class="fas fa-check-double fa-lg me-3"></i>
                <strong class="me-2">{{ count($bulkSelection['ids']) }}</strong> Selected
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="form-check me-3">
                    <input type="checkbox" class="form-check-input" wire:model.live="bulkSelection.all" />
                    <strong class="text-white ms-1">Select All</strong>
                </div>

                {{-- Primary bulk actions as separate buttons --}}
                @php
                    $primaryBulkActions = ['export', 'delete', 'restore'];
                    $moreBulkActions = array_diff(array_keys($bulkActions), $primaryBulkActions);
                @endphp

                @foreach ($primaryBulkActions as $actionKey)
                    @if (isset($bulkActions[$actionKey]))
                        <button class="btn btn-sm btn-outline-white text-white"
                            wire:click.prevent="handleBulkAction('{{ $actionKey }}')">
                            @if (!empty($bulkActions[$actionKey]['icon']))
                                <i class="{{ $bulkActions[$actionKey]['icon'] }} me-1"></i>
                            @endif
                            {{ $bulkActions[$actionKey]['label'] }}
                        </button>
                    @endif
                @endforeach

                {{-- More dropdown for secondary bulk actions --}}
                @if (!empty($moreBulkActions))
                    <div class="dropdown">
                        <button class="btn btn-sm btn-white dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            More
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                            @foreach ($moreBulkActions as $actionKey)
                                @php $action = $bulkActions[$actionKey]; @endphp
                                <li>
                                    <a class="dropdown-item" href="#"
                                        wire:click.prevent="handleBulkAction('{{ $actionKey }}')">
                                        @if (!empty($action['icon']))
                                            <i class="{{ $action['icon'] }} me-2"></i>
                                        @endif
                                        {{ $action['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif




    {{-- Table View --}}
    @if ($viewMode === 'table')
        <div class="table-responsive" style="min-height: 500px">
            <table class="table align-items-center mb-0 table-striped {{ $density === 'compact' ? 'table-sm' : '' }}">

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
                                            style="font-size: 0.75rem; cursor: pointer;"
                                            wire:click.stop="openColumnFilter('{{ $name }}')">
                                        </i>
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
                        @php
                            $isTrashed = $this->usesSoftDeletes() && $this->isTrashed($record);
                        @endphp

                        <tr wire:key="row-{{ $record->id }}-{{ $loop->index }}"
                            class="align-middle transition-base {{ $isTrashed ? 'bg-light opacity-75' : 'hover-bg-subtle' }}">

                            @if (!empty($controls['bulkActions']))
                                <td class="ps-3" style="width: 40px;">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input shadow-none"
                                            wire:model.live="bulkSelection.ids" value="{{ $record->id }}">
                                    </div>
                                </td>
                            @endif


                            @foreach ($visibleColumns as $name)
                                @php
                                    $def = $columns[$name];
                                    $rowKey = 'row_' . $record->id;
                                    $isEditable = $this->editable && ($def['editable'] ?? false);
                                    $isEditing = isset($this->editMode[$rowKey][$name]);
                                    $cellValue = $this->editedData[$rowKey][$name] ?? $this->getValueFromRecord($record, $name);
                                @endphp
                                <td class="{{ $isTrashed ? 'text-decoration-line-through text-muted' : '' }};">
                                    @if ($isEditable && $isEditing)
                                        <div class="d-flex align-items-center">
                                            <!-- We are in edit mode -->

                                            {!! $this->getField($name, $def)->renderInlineEditor($cellValue, $record, [
                                                'rowId' => $record->id,
                                                'wire:model' => "editedData.{$rowKey}.{$name}",
                                                'configKey' => $this->configKey,
                                                'fieldName' => $name, // pass field name
                                            ]) !!}
                                            <button
                                                wire:click="saveCell({{ $record->id }}, '{{ $name }}', $event.target.value)"
                                                class="btn btn-sm btn-success  px-2" title="Save">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button
                                                wire:click="cancelEditingCell({{ $record->id }}, '{{ $name }}')"
                                                class="btn btn-sm btn-secondary  px-2" title="Cancel">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @else
                                        <div
                                            @if ($isEditable) wire:dblclick="startEditingCell({{ $record->id }}, '{{ $name }}')" 
                    style="cursor: pointer;" @endif>
                                            {!! $this->getField($name, $def)->renderTable($this->getValueFromRecord($record, $name), $record) !!}

                                            
                                        </div>
                                        
                                    @endif
                                </td>
                            @endforeach

                            {{-- Add Row Actions --}}
                            <td class="text-end pe-3">
                                @include('qf::livewire.data-tables.partials.row-actions')
                            </td>
                        </tr>

                        @if (isset($simpleActions['expand']))
                            <tr wire:key="expand-row-{{ $record->id }}" class="expandable-row">
                                <td
                                    colspan="{{ count($visibleColumns) + (empty($controls['bulkActions']) ? 0 : 1) + (!empty($simpleActions) || !empty($moreActions) ? 1 : 0) }}">
                                    @livewire('qf.collapsible', ['collapsibleId' => 'expand-' . $record->id], key('expand-' . $record->id))
                                </td>
                            </tr>
                        @endif


                    @empty
                        <tr>
                            <td colspan="100%" class="py-5 text-center">
                                <div class="text-muted opacity-50 mb-2">
                                    <i class="fas fa-inbox fa-3x"></i>
                                </div>
                                <h6 class="text-muted fw-normal">No records found matching your criteria.</h6>
                            </td>
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
            'crudType' => $crudType,
            'modelName' => $modelName,
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
            'crudType' => $crudType,
            'modelName' => $modelName,
        ])

        {{-- Monthly View --}}
    @elseif($viewMode === 'monthly')
        @include('qf::livewire.data-tables.partials.monthly-view', [
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
        @if (method_exists($records, 'links'))
            {{ $records->links() }}
        @endif
    </div>










    @if ($filterModalOpen)
        <div class="modal fade show d-block" id="columnFilterModal" tabindex="-1"
            style="background-color: rgba(0,0,0,0.5);" wire:ignore.self>
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Filter by
                            {{ $columns[$columnFilterField]['label'] ?? ucfirst($columnFilterField) }}</h5>
                        <button type="button" class="btn-close" wire:click="closeColumnFilter"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $def = $columns[$columnFilterField] ?? [];
                            $type = $def['field_type'] ?? 'string';
                            $filterType = $this->mapFieldTypeToFilterType($type);
                            $options = $def['options'] ?? [];
                        @endphp

                        @switch($filterType)
                            @case('select')
                                <select wire:model="columnFilterValue" class="form-select">
                                    <option value="">All</option>
                                    @foreach ($options as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            @break

                            @case('boolean')
                                <select wire:model="columnFilterValue" class="form-select">
                                    <option value="">All</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            @break

                            @case('date')
                                <input type="date" wire:model="columnFilterValue" class="form-control">
                            @break

                            @case('number')
                                <input type="number" step="any" wire:model="columnFilterValue" class="form-control"
                                    placeholder="Equals">
                            @break

                            @default
                                <input type="text" wire:model="columnFilterValue" class="form-control"
                                    placeholder="Contains...">
                        @endswitch
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="closeColumnFilter">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="applyColumnFilter">Apply</button>
                        <button type="button" class="btn btn-link text-danger"
                            wire:click="clearColumnFilter">Clear</button>
                    </div>
                </div>
            </div>
        </div>
    @endif










    <style>
        th.sorting.sorting-asc,
        th.sorting.sorting-desc {
            /* background-color: rgba(0, 123, 255, 0.1); */
        }
    </style>




    <style>
        /* Professional Row Hover */
        .hover-bg-subtle:hover {
            background-color: rgba(0, 0, 0, 0.015) !important;
        }

        /* Fixed-size Action Buttons */
        .btn-action-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: none;
            background: transparent;
            color: #adb5bd;
            border-radius: 6px;
        }

        /* Hover States with soft backgrounds */
        .text-info-hover:hover {
            color: #0dcaf0 !important;
            background-color: rgba(13, 202, 240, 0.1);
        }

        .text-primary-hover:hover {
            color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.1);
        }

        .text-danger-hover:hover {
            color: #dc3545 !important;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .btn-action-icon:hover:not([class*='hover']) {
            background-color: #f8f9fa;
            color: #212529;
        }

        /* Clean Dropdown */
        .dropdown-menu {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
            border-radius: 8px;
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: 4px;
            transition: all 0.1s ease;
        }

        .no-caret::after {
            display: none;
        }

        .transition-base {
            transition: all 0.15s ease-in-out;
        }

        /* Clean Checkboxes */
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>

    <style>
        /* Make dropdowns visible in list and card views */
        .list-view,
        .card {
            overflow: visible !important;
        }

        /* Also ensure the card's image area doesn't break (optional) */
        .card .card-img-top {
            border-radius: 12px 12px 0 0;
        }


        .btn-sm {
            padding: 0.5em 1em;
        }

        .btn-sm,
        .input-group-sm,
        .form-select-sm {
            margin: 0em 0.3em;
        }

        .input-group-sm input,
        .input-group-sm button {
            height: 2.6em;
            padding: 0.2em;
        }

        .input-group-sm {
            margin-top: 1em;
        }

        .btn-sm i {
            margin-right: 0.5em;
        }
    </style>





</div>
