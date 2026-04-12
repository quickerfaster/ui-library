<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 card-view-grid">

    
    @forelse($records as $record)
        @php
            $module = strtolower($this->getConfigResolver()->getModuleName());
            $modelPlural = \Str::plural(\Str::kebab($this->getConfigResolver()->getModelName()));
            $params = $this->returnParams ?? [];
            $showUrl = url("/{$module}/{$modelPlural}/{$record->id}") . (!empty($params) ? '?' . http_build_query($params) : '');
        @endphp

        <div class="col" wire:key="card-{{ $record->id }}">
            <div class="card h-100 border-0 shadow-sm transition-all hover-lift overflow-hidden position-relative" 
                 onclick="if(!event.target.closest('.stop-propagation')) { window.location='{{ $showUrl }}' }"
                 style="cursor: pointer; border-radius: 12px;">
                
                {{-- 1. TOP-LEFT BULK SELECTION --}}
                @if($bulkSelection)
                    <div class="position-absolute top-0 start-0 m-3 stop-propagation" style="z-index: 10;">
                        <div class="form-check custom-card-checkbox">
                            <input type="checkbox" 
                                   class="form-check-input shadow-sm" 
                                   wire:model.live="selectedRecords" 
                                   value="{{ $record->id }}"
                                   style="width: 1.2rem; height: 1.2rem; cursor: pointer; border-width: 2px;">
                        </div>
                    </div>
                @endif

                {{-- 2. Image / Icon Header Section --}}
                @php
                    $imageUrl = !empty($viewConfig['imageField']) ? $this->getValueFromRecord($record, $viewConfig['imageField']) : null;
                    $iconClass = !empty($viewConfig['iconField']) ? $this->getValueFromRecord($record, $viewConfig['iconField']) : ($viewConfig['defaultIconClass'] ?? 'fas fa-cube');
                @endphp

                <div class="position-relative">
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" class="card-img-top" alt="Header" style="height: 140px; object-fit: cover;">
                    @else
                        <div class="card-img-top d-flex align-items-center justify-content-center bg-gradient-light py-5" 
                             style="height: 140px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <i class="{{ $iconClass }} fa-3x text-muted op-3"></i>
                        </div>
                    @endif

                    {{-- Floating Badge (Top-Right) --}}
                    @if(!empty($viewConfig['badgeField']))
                        @php 
                            $val = $record->{$viewConfig['badgeField']};
                            $color = ($viewConfig['badgeColors'] ?? [])[$val] ?? 'secondary';
                        @endphp
                        <div class="position-absolute top-0 end-0 m-3 stop-propagation">
                            <span class="badge rounded-pill bg-white text-{{ $color }} shadow-sm border border-{{ $color }} px-2 py-1" style="font-size: 0.65rem;">
                                {{ $val ? 'ACTIVE' : 'INACTIVE' }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- 3. Card Body --}}
                <div class="card-body p-4">
                    @if(!empty($viewConfig['titleFields']))
                        <h5 class="fw-bold text-dark mb-1 text-truncate">
                            @foreach($viewConfig['titleFields'] as $field)
                                <span>{{ $this->getValueFromRecord($record, $field) }}</span>
                                @if(!$loop->last) <span class="mx-1 text-muted">·</span> @endif
                            @endforeach
                        </h5>
                    @endif

                    @if(!empty($viewConfig['subtitleFields']))
                        <div class="small text-muted mb-3 fw-medium">
                            @foreach($viewConfig['subtitleFields'] as $field)
                                <span>{{ $this->getValueFromRecord($record, $field) }}</span>
                                @if(!$loop->last) <span class="mx-1">•</span> @endif
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($viewConfig['contentFields']))
                        <div class="card-text border-top pt-3 mt-auto">
                            @foreach($viewConfig['contentFields'] as $field)
                                @php $def = $this->columns[$field] ?? null; @endphp
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span class="text-muted fw-semibold small text-uppercase">{{ $field }}:</span>
                                    <span class="text-dark fw-medium">
                                        {!! $def ? $this->getField($field, $def)->renderTable($record->$field, $record) : $this->getValueFromRecord($record, $field) !!}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- 4. Card Footer (Actions) --}}
                <div class="card-footer bg-white border-0 px-4 pb-4 pt-0 d-flex justify-content-end stop-propagation">
                    <div class="op-2-hover">
                        @include('qf::livewire.data-tables.partials.row-actions', [
                            'record' => $record,
                            'simpleActions' => $simpleActions,
                            'moreActions' => $moreActions,
                            'controls' => $controls,
                            'bulkSelection' => $bulkSelection,
                            'configKey' => $configKey,
                        ])
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center py-5 text-muted">No records found.</div>
    @endforelse
</div>

<style>
    /* Premium Selection Styling */
    .custom-card-checkbox .form-check-input:not(:checked) {
        opacity: 0.2;
        transition: opacity 0.2s;
    }
    .card:hover .custom-card-checkbox .form-check-input {
        opacity: 1;
    }
    .custom-card-checkbox .form-check-input:checked {
        opacity: 1;
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    /* Highlight the card when selected */
    .card:has(.form-check-input:checked) {
        border: 2px solid #0d6efd !important;
        background-color: #f0f7ff !important;
    }
</style>
