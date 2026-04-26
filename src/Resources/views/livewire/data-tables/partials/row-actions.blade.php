<div class="d-flex align-items-center gap-1 stop-propagation">
    @if (!empty($simpleActions) && !$this->isTrashed($record))
        @php
            $routePrefix = Str::plural(Str::kebab($modelName));
            // Refined class: simplified transitions and layout
            $btnClass = 'btn btn-action-icon d-inline-flex align-items-center justify-content-center transition-base';
        @endphp

        @if (in_array('show', $simpleActions))
            <a href="{{ ($viewType ?? '') === 'pages' ? route($routePrefix . '.show', ['id' => $record->id] + ($queryParams ?? [])) : 'javascript:void(0)' }}"
                @if (($viewType ?? '') !== 'pages') wire:click="show({{ $record->id }})" @endif
                class="{{ $btnClass }} text-info-hover" title="View Detail">
                <i class="fas fa-eye"></i>
            </a>
        @endif

        @if (in_array('edit', $simpleActions))
            <a href="{{ ($viewType ?? '') === 'pages' ? route($routePrefix . '.edit', ['id' => $record->id] + ($queryParams ?? [])) : 'javascript:void(0)' }}"
                @if (($viewType ?? '') !== 'pages') wire:click="edit({{ $record->id }})" @endif
                class="{{ $btnClass }} text-primary-hover" title="Edit Record">
                <i class="fas fa-pencil-alt"></i> {{-- Swapped for a cleaner edit icon --}}
            </a>
        @endif

        @if (in_array('delete', $simpleActions))
            <button wire:click="confirmDelete({{ $record->id }})" class="{{ $btnClass }} text-danger-hover"
                title="Delete">
                <i class="fas fa-trash-alt"></i>
            </button>
        @endif
    @else
        <span class="badge rounded-pill bg-light text-secondary border fw-medium px-2">Deleted</span>
    @endif

    {{-- More Actions Dropdown --}}
    @if (!empty($moreActions))
        <div class="dropdown table-dropdown">
            <button class="btn btn-action-icon no-caret" type="button" data-bs-toggle="dropdown"
                data-bs-auto-close="true">
                <i class="fas fa-ellipsis-v"></i> {{-- Vertical dots look more modern --}}
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                @foreach ($moreActions as $index => $action)
                    <li>
                        <a class="dropdown-item d-flex align-items-center py-2" href="#"
                            wire:click.prevent="handleRowAction({{ $index }}, {{ $record->id }})">
                            @if (!empty($action['icon']))
                                <i class="{{ $action['icon'] }} opacity-50 me-2" style="width: 1.25rem;"></i>
                            @endif
                            <span class="small">{{ $action['title'] }}</span>
                        </a>
                    </li>
                    @if (!empty($action['appendSeparator']))
                        <li>
                            <hr class="dropdown-divider opacity-50">
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif
</div>
