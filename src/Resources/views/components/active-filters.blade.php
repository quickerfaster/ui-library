
@if(count($this->activeFilters))
    <div class="active-filters mb-3 d-flex flex-wrap gap-2 align-items-center">
        @foreach($this->activeFilters as $filter)
            @if (isset($filter['label']) && isset($filter['displayValue']) && isset($filter['field']))
                <span class="badge bg-primary d-inline-flex align-items-center gap-1">
                    <i class="fas fa-filter me-1"></i>
                    {{ $filter['label'] }}:
                    @if(is_array($filter['displayValue']))
                        {{ implode(', ', $filter['displayValue']) }}
                    @else
                        {{ $filter['displayValue'] }}
                    @endif
                    <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.6rem;" wire:click="removeFilter('{{ $filter['field'] }}')"></button>
                </span>
            @endif
        @endforeach
        <button wire:click="clearAllFilters" class="btn btn-sm btn-link text-decoration-none">
            <i class="fas fa-times me-1"></i>Clear all filters
        </button>
    </div>
@endif