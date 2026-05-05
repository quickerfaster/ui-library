@props(['searchTerm' => '', 'columns' => [], 'exactMatch' => false, 'columnsLabels' => []])

@if(!empty($searchTerm))
    <div class="active-search mb-3 d-flex flex-wrap gap-2 align-items-center">
        {{-- Search badge --}}
        <span class="badge bg-info d-inline-flex align-items-center gap-1">
            <i class="fas fa-search me-1"></i>
            Search: "{{ $searchTerm }}"
            @if($exactMatch)
                <span class="badge bg-dark ms-1">exact match</span>
            @endif
            @if(!empty($columns))
                <span class="badge bg-secondary ms-1">
                    {{ count($columns) }} column{{ count($columns) !== 1 ? 's' : '' }}
                </span>
            @endif
            <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.6rem;" wire:click="clearSearch"></button>
        </span>

        {{-- Optionally show which columns are being searched (collapsible/tooltip) --}}
        @if(!empty($columns) && count($columns) <= 5)
            <span class="text-muted small">
                Searching in:
                @foreach($columns as $field)
                    <span class="badge bg-light text-dark">{{ $columnsLabels[$field] ?? $field }}</span>
                @endforeach
            </span>
        @elseif(!empty($columns))
            <span class="text-muted small">
                Searching in {{ count($columns) }} columns
                <span class="badge bg-light text-dark" style="cursor: help;" title="{{ implode(', ', array_map(fn($f) => $columnsLabels[$f] ?? $f, $columns)) }}">
                    <i class="fas fa-info-circle"></i>
                </span>
            </span>
        @endif
    </div>
@endif