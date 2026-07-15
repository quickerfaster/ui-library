@if ($layoutConfig['titleRow']['enabled'] ?? true)
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <div>
            @if ($layoutConfig['title']['enabled'] ?? true)
                <h1 class="h3 mb-0">{{ $pageTitle }}</h1>
            @endif
            @include('qf::components.layouts.partials.company-title-suffix', ['asBadge' => true])
        </div>


        @php
            $primaryAction = null;
            $secondaryActions = collect();
            $addButtonConfig = $configResolver->getControls()['addButton'] ?? null;

            // Capture current list state
            $stateParams = request()->only(['page', 'perPage', 'search', 'sort', 'activeFilters']);
            $queryString = http_build_query($stateParams);

            /**
             * Helper to resolve URL and inject state
             */
            $resolveUrl = function ($control) use ($queryString, $configResolver) {
                if (!$control) {
                    return '#';
                }

                // 1. Resolve Base
                if (!empty($control['url'])) {
                    $base = $control['url'];
                } elseif (!empty($control['route'])) {
                    $base = route($control['route']);
                } else {
                    $modelPlural = \Str::plural(\Str::kebab($configResolver->getModelName()));
                    $base = "/{$modelPlural}/create";
                }

                // 2. Inject State
                if (empty($queryString)) {
                    return $base;
                }
                return $base . (str_contains($base, '?') ? '&' : '?') . $queryString;
            };

            // Normalize addButton configuration
            if ($addButtonConfig === true) {
                // Simple boolean true → create a default primary action
                $primaryAction = [
                    'label' => 'New ' . $configResolver->getModelName(),
                    'icon' => 'fas fa-plus-circle',
                    'primary' => true,
                ];
                $secondaryActions = collect();
            } elseif (is_array($addButtonConfig)) {
                $controlsCollection = collect($addButtonConfig);
                $primaryAction = $controlsCollection->firstWhere('primary', true) ?: $controlsCollection->first();
                $secondaryActions = $controlsCollection->filter(function ($control) use ($primaryAction) {
                    return $control !== $primaryAction && !($control['primary'] ?? false);
                });
            }

            $finalCreateUrl = $resolveUrl($primaryAction);
        @endphp



        @if ($addButtonConfig)
            <div class="btn-group">
                @if ($crudType === 'pages')
                    <a href="{{ $finalCreateUrl }}" wire:navigate
                        class="btn btn-sm btn-primary bg-gradient-primary d-inline-flex align-items-center">
                        <i class="{{ $primaryAction['icon'] ?? 'fas fa-plus-circle' }} me-1"></i>
                        {{ $primaryAction['label'] ?? 'New ' . $configResolver->getModelName() }}
                    </a>
                @elseif ($crudType === 'drawers')
                    <button type="button" class="btn btn-sm btn-primary bg-gradient-primary"
                        onclick="Livewire.dispatch('openDrawer', {
                        component: 'qf.data-table-form',
                        params: {
                            configKey: '{{ $configKey }}',
                            inline: true,
                            prefilledData: {{ json_encode(request()->except(['page', 'perPage', 'search', 'sort', 'activeFilters'])) }}
                        },
                        title: '{{ $primaryAction['label'] ?? 'New ' . $configResolver->getModelName() }}'
                    })">
                        <i class="{{ $primaryAction['icon'] ?? 'fas fa-plus-circle' }} me-1"></i>
                        {{ $primaryAction['label'] ?? 'New ' . $configResolver->getModelName() }}
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-primary bg-gradient-primary"
                        onclick="Livewire.dispatch('openAddModal', { configKey: '{{ $configKey }}' })">
                        <i class="{{ $primaryAction['icon'] ?? 'fas fa-plus-circle' }} me-1"></i>
                        {{ $primaryAction['label'] ?? 'New ' . $configResolver->getModelName() }}
                    </button>
                @endif

                @if ($secondaryActions->isNotEmpty())
                    <button type="button"
                        class="btn btn-sm btn-primary bg-gradient-primary dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        @foreach ($secondaryActions as $control)
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ $resolveUrl($control) }}">
                                    <i class="{{ $control['icon'] ?? 'fas fa-link' }} me-2 text-muted"></i>
                                    {{ $control['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
@endif

@if ($layoutConfig['breadcrumb']['enabled'] ?? true)
    <x-breadcrumb :items="$breadcrumbItems" />
@endif
