                @if ($layoutConfig['titleRow']['enabled'] ?? true)

                    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                        <div>
                            @if ($layoutConfig['title']['enabled'] ?? true)
                                <h1 class="h3 mb-0">{{ $pageTitle }}</h1>
                            @endif
                        </div>

                        @php

                            $primaryAction = null;
                            //dd($configResolver->getControls());
                            $addButton = $controls = $configResolver->getControls()['addButton'];
                            if (is_array($addButton)) {
                                $primaryAction =
                                    collect($controls)->firstWhere('primary', true) ?: $controls[0] ?? null;
                            }

                            // Generate the dynamic "Pages" URL
                            $modelPlural = \Str::plural(\Str::kebab($configResolver->getModelName()));
                            $createUrl = "/{$modelPlural}/create";

                            // Capture current list state to pass to the create page
                            $returnState = http_build_query(
                                request()->only(['page', 'perPage', 'search', 'sort', 'activeFilters']),
                            );
                            $finalCreateUrl = $createUrl . ($returnState ? '?' . $returnState : '');
                        @endphp

                        @if ($addButton)
                            <div class="btn-group">

                                @if ($viewType === 'pages')
                                    {{-- CASE 1: Standard Link for Page-based Navigation --}}
                                    <a href="{{ $finalCreateUrl }}" wire:navigate
                                        class="btn btn-sm btn-primary bg-gradient-primary d-inline-flex align-items-center">
                                        @if ($primaryAction)
                                            <i class="{{ $primaryAction['icon'] }}  me-1"></i>
                                            {{ $primaryAction['label'] }}
                                        @else
                                            <i class="fas fa-plus-circle me-1"></i>
                                            New {{ $configResolver->getModelName() }}
                                        @endif
                                    </a>
                                @else
                                    {{-- CASE 2: Button for Livewire Modal Dispatch --}}
                                    <button type="button" class="btn btn-sm btn-primary bg-gradient-primary"
                                        onclick="Livewire.dispatch('openAddModal', { configKey: '{{ $configKey }}' })">
                                        @if ($primaryAction)
                                            <i class="{{ $primaryAction['icon'] }}  me-1"></i>
                                            {{ $primaryAction['label'] }}
                                        @else
                                            <i class="fas fa-plus-circle  me-1"></i>
                                            New {{ $configResolver->getModelName() }}
                                        @endif
                                    </button>
                                @endif

                                @if ($primaryAction)
                                    <!-- Dropdown for secondary controls -->
                                    <button type="button"
                                        class="btn btn-sm btn-primary bg-gradient-primary dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="visually-hidden">Toggle Dropdown</span>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        @foreach ($controls as $control)
                                            @if (!($control['primary'] ?? false))
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center"
                                                        href="{{ $control['url'] ?? '#' }}">
                                                        <i class="{{ $control['icon'] }} me-2 text-muted"></i>
                                                        {{ $control['label'] }}
                                                    </a>
                                                </li>
                                            @endif
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
