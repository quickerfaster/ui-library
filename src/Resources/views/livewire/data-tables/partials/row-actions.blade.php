@php
    $isTrashed = $this->isTrashed($record) ?? false;
    $crudType = $crudType ?? 'modal';
    $routePrefix = Str::plural(Str::kebab($modelName));
    $queryParams = $queryParams ?? [];
    $isPage = $crudType === 'pages';
    $user = auth()->user();
    $authService = app(\QuickerFaster\UILibrary\Services\DataTables\DefaultAuthorizationProvider::class);

    // Pre‑filter moreActions to only those the user can perform
    $visibleMoreActions = [];
    foreach ($moreActions as $index => $action) {
        if ($authService->canPerformAction($user, $action, $record)) {
            $visibleMoreActions[] = ['index' => $index, 'action' => $action];
        }
    }
@endphp

<div class="d-flex justify-content-end align-items-center gap-1 stop-propagation">
    @if (!$isTrashed)
        @php
            $btnClass = 'btn btn-action-icon transition-base';
        @endphp

        {{-- SHOW action --}}
        @if (in_array('show', $simpleActions) && $authService->canView($user, $record))
            @if ($isPage)
                <a href="{{ route($routePrefix . '.show', ['id' => $record->id] + $queryParams) }}"
                    class="{{ $btnClass }} text-info-hover" title="View">
                    <i class="fas fa-eye"></i>
                </a>
            @elseif ($crudType === 'drawers')
                <button type="button"
                    wire:click="$dispatch('openDrawer', { 
                        component: 'qf.data-table-detail', 
                        params: { configKey: '{{ $configKey }}', recordId: {{ $record->id }}, inline: true },
                        title: 'View {{ $modelName }}'
                    })"
                    class="{{ $btnClass }} text-info-hover" title="View">
                    <i class="fas fa-eye"></i>
                </button>
            @else
                <button wire:click="show({{ $record->id }})" class="{{ $btnClass }} text-info-hover"
                    title="View">
                    <i class="fas fa-eye"></i>
                </button>
            @endif
        @endif

        {{-- EDIT action --}}
        @if (in_array('edit', $simpleActions) && $authService->canUpdate($user, $record))
            @if ($isPage)
                <a href="{{ route($routePrefix . '.edit', ['id' => $record->id] + $queryParams) }}"
                    class="{{ $btnClass }} text-primary-hover" title="Edit">
                    <i class="fas fa-pencil-alt"></i>
                </a>
            @elseif ($crudType === 'drawers')
                <button type="button"
                    wire:click="$dispatch('openDrawer', { 
                        component: 'qf.data-table-form', 
                        params: { configKey: '{{ $configKey }}', recordId: {{ $record->id }}, inline: true },
                        title: 'Edit {{ $modelName }}'
                    })"
                    class="{{ $btnClass }} text-primary-hover" title="Edit">
                    <i class="fas fa-pencil-alt"></i>
                </button>
            @else
                <button wire:click="edit({{ $record->id }})" class="{{ $btnClass }} text-primary-hover"
                    title="Edit">
                    <i class="fas fa-pencil-alt"></i>
                </button>
            @endif
        @endif

        {{-- DELETE action --}}
        @if (in_array('delete', $simpleActions) && $authService->canDelete($user, $record))
            <button wire:click="confirmDelete({{ $record->id }})" class="{{ $btnClass }} text-danger-hover"
                title="Delete">
                <i class="fas fa-trash-alt"></i>
            </button>
        @endif

        {{-- Expand action (no permission check – optional) --}}
        @if (isset($simpleActions['expand']))
            @php
                $expandConfig = is_array($simpleActions['expand'])
                    ? $simpleActions['expand']
                    : ['component' => 'qf.data-table-detail', 'params' => []];
                $expandComponent = $expandConfig['component'] ?? 'qf.data-table-detail';
                $expandParams = array_merge(
                    [
                        'configKey' => $configKey,
                        'recordId' => $record->id,
                        'inline' => true,
                    ],
                    $expandConfig['params'] ?? [],
                );
                $expandTitle = $expandConfig['label'] ?? 'Details';
                $expandIcon = $expandConfig['icon'] ?? 'fas fa-chevron-down';
            @endphp

            <button type="button"
                wire:click="$dispatch('toggleCollapsible', { 
                    collapsibleId: 'expand-{{ $record->id }}',
                    component: '{{ $expandComponent }}', 
                    params: {{ json_encode($expandParams) }},
                    title: '{{ $expandTitle }}',
                    target: 'expand-{{ $record->id }}'
                })"
                class="{{ $btnClass }} text-info-hover" title="{{ $expandTitle }}">
                <i class="{{ $expandIcon }}"></i>
            </button>
        @endif
    @else
        {{-- Trashed record: show badge + restore / force delete buttons --}}
        <span class="badge rounded-pill bg-white text-secondary border fw-medium px-2 small">Deleted</span>

        @if (in_array('restore', $simpleActions) && $authService->canRestore($user, $record)) 
            <button wire:click="restore({{ $record->id }})" class="btn btn-action-icon text-success-hover" title="Restore">
                <i class="fas fa-trash-restore"></i>
            </button>
        @endif

        @if (in_array('forceDelete', $simpleActions) && $authService->canForceDelete($user, $record))
            <button wire:click="forceDelete({{ $record->id }})" class="btn btn-action-icon text-danger-hover" title="Permanently Delete">
                <i class="fas fa-skull-crossbones"></i>
            </button>
        @endif
    @endif

    {{-- More actions dropdown – only show if there is at least one visible action --}}
    @if (!empty($visibleMoreActions))
        <div class="dropdown">
            <button class="btn btn-action-icon no-caret" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                @foreach ($visibleMoreActions as $item)
                    @php
                        $index = $item['index'];
                        $action = $item['action'];
                    @endphp
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
                        <li><hr class="dropdown-divider opacity-50"></li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif



</div>