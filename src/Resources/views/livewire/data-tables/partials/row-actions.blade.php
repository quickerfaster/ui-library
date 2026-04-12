<div class="d-flex align-items-center gap-1 stop-propagation"> {{-- Added stop-propagation for our row-click feature --}}

    @if (!empty($simpleActions) && !$this->isTrashed($record))
        @php
            $routePrefix = Str::plural(Str::kebab($modelName));
            // Shared class for all ghost buttons
            $btnClass = 'btn btn-sm btn-ghost p-1 text-muted transition-all hover-scale';
        @endphp

        {{-- Show Action --}}
        @if (in_array('show', $simpleActions))
            @php
                $queryParams = [
                    'page' => $this->getPage(),
                    'perPage' => $this->perPage,
                    'search' => $this->search,
                    'sort' => json_encode($this->sort),
                    'activeFilters' => json_encode($this->activeFilters ?? []),
                ];
            @endphp
            <a href="{{ ($viewType ?? '') === 'pages' ? route($routePrefix . '.show', array_merge(['id' => $record->id], $queryParams)) : 'javascript:void(0)' }}"
                @if (($viewType ?? '') !== 'pages') wire:click="show({{ $record->id }})" @endif
                class="{{ $btnClass }} hover-text-info" title="View Detail">
                <i class="fas fa-eye"></i>
            </a>
        @endif

        {{-- Edit Action --}}
        @if (in_array('edit', $simpleActions))
            <a href="{{ ($viewType ?? '') === 'pages' ? route($routePrefix . '.edit', array_merge(['id' => $record->id], ['page' => $this->getPage(), 'perPage' => $this->perPage, 'search' => $this->search, 'activeFilters' => $this->activeFilters ?? []])) : 'javascript:void(0)' }}"
                @if (($viewType ?? '') !== 'pages') wire:click="edit({{ $record->id }})" @endif
                class="{{ $btnClass }} hover-text-primary" title="Edit Record">
                <i class="fas fa-edit"></i>
            </a>
        @endif

        {{-- Delete Action --}}
        @if (in_array('delete', $simpleActions))
            <button wire:click="confirmDelete({{ $record->id }})" class="{{ $btnClass }} hover-text-danger"
                title="Delete">
                <i class="fas fa-trash-alt"></i>
            </button>
        @endif
    @else
        <span class="badge bg-secondary me-2">Deleted</span>
    @endif

    {{-- More Actions Dropdown --}}
    @if (!empty($moreActions) && $trashedFilter == "only")
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach ($moreActions as $index => $action)
                    <li>
                        <a class="dropdown-item" href="#"
                            wire:click.prevent="handleRowAction({{ $index }}, {{ $record->id }})">
                            @if (!empty($action['icon']))
                                <i class="{{ $action['icon'] }} me-2"></i>
                            @endif
                            {{ $action['title'] }}
                        </a>
                    </li>
                    @if (!empty($action['appendSeparator']))
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif







</div>

<style>
    /* Premium Hover Styles */
    .btn-ghost {
        background: transparent;
        border: none;
    }

    .btn-ghost:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .hover-text-info:hover {
        color: #0dcaf0 !important;
    }

    .hover-text-primary:hover {
        color: #0d6efd !important;
    }

    .hover-text-danger:hover {
        color: #dc3545 !important;
    }

    .hover-scale {
        transition: transform 0.1s ease;
    }

    .hover-scale:hover {
        transform: scale(1.15);
    }

    /* Remove Bootstrap default caret from the ellipsis button */
    .no-caret::after {
        display: none;
    }
</style>
