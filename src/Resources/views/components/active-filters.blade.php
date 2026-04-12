@props(['filters' => []])

@if(count($filters))
    <div class="active-filters mb-3 d-flex flex-wrap gap-2">
        @foreach($filters as $filter)
            <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
                {{ $filter['label'] }}: 
                @if(is_array($filter['displayValue']))
                    {{ implode(', ', $filter['displayValue']) }}
                @else
                    {{ $filter['displayValue'] }}
                @endif
                <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.6rem;" wire:click="removeFilter('{{ $filter['field'] }}')"></button>
            </span>
        @endforeach
        <button wire:click="clearAllFilters" class="btn btn-sm btn-link">Clear all</button>
    </div>
@endif