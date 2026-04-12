  <div>  
    <div class="detail-page-wrapper mb-5 p-2">
{{-- 1. HEADER SECTION --}}
    @php
        $module = strtolower($this->getConfigResolver()->getModuleName());
        // Get Model Name and make it readable (e.g. JobTitle -> Job Title)
        $modelName = $this->getConfigResolver()->getModelName();
        $displayModelName = ucwords(str_replace(['_', '-'], ' ', \Str::snake($modelName)));
        
        $modelPlural = \Str::plural(\Str::kebab($modelName));
        $params = $this->returnParams ?? [];
        $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        $backUrl = url("/{$module}/{$modelPlural}" . $queryString);
    @endphp

    <div class="d-flex justify-content-between align-items-center my-4 d-print-none">
        <div class="d-flex flex-column">
            {{-- Professional Back Link --}}
            <a href="{{ $backUrl }}" class="text-decoration-none text-muted small fw-bold mb-2 d-inline-flex align-items-center hover-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to {{ \Str::plural($displayModelName) }}
            </a>

            <div class="d-flex align-items-center">
                <h2 class="fw-bold text-dark mb-0">{{ $displayModelName }} Details</h2>
                <span class="badge bg-light text-secondary border ms-3">ID: #{{ $record->id }}</span>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            @php
                $editUrl = url("/{$modelPlural}/{$record->id}/edit" . $queryString);
            @endphp

            <a type="button" onclick="window.print();" class="btn btn-outline-secondary shadow-sm px-3">
                <i class="fas fa-print me-1"></i> Print
            </a>
            <a href="{{ $editUrl }}" class="btn btn-primary bg-gradient-primary shadow-sm px-4">
                <i class="fas fa-edit me-1"></i> Edit {{ $displayModelName }}
            </a>
        </div>
    </div>


        {{-- 2. DATA GROUPS --}}
        <div class="row g-4">
            @forelse($fieldGroups as $group)
                <div class="col-12 col-xl-6"> {{-- Two groups side-by-side on wide screens --}}
                    <div class="card border-0 shadow-sm h-100">
                        @if (!empty($group['title']))
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                                <h5 class="fw-bold text-primary mb-0">{{ $group['title'] }}</h5>
                            </div>
                        @endif
                        <div class="card-body p-4">
                            <div class="row gy-3">
@foreach ($group['fields'] as $field)
    @if (!in_array($field, $hiddenFields['onDetail'] ?? []))
        @php
            $definition = $fieldDefinitions[$field];
            $fieldObj = $this->getField($field);
        @endphp
        <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
            {{ $fieldObj->getLabel() }}
        </div>
        <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
            @if(($definition['field_type'] ?? '') === 'morph_to_select' && isset($definition['morph_relation']))
                @php
                    $related = $record->{$definition['morph_relation']};
                    $displayValue = $related ? $related->{$definition['display_field'] ?? 'name'} : '';
                @endphp
                {{ $displayValue ?: '<span class="text-muted italic">-</span>' }}
            @else
                {!! $fieldObj->renderDetail($record->$field) ?? '<span class="text-muted italic">-</span>' !!}
            @endif
        </div>
    @endif
@endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Fallback logic similar to above but with the new grid style --}}
            @endforelse
        </div>
    </div>

    <style>
        /* Premium Detail Styling */
        .detail-page-wrapper { font-size: 0.95rem; }
        .breadcrumb-item a { color: #6c757d; }
        .card { border-radius: 12px; }
        
        /* Clean printing */
        @media print {
            .d-print-none, .btn, nav, .sidebar { display: none !important; }
            .card { border: none !important; shadow: none !important; }
            .col-sm-4 { width: 30% !important; float: left; }
            .col-sm-8 { width: 70% !important; float: left; }
        }
    </style>
</div>



