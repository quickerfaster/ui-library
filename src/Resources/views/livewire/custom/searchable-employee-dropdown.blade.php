<div class="searchable-employee-dropdown position-relative p-0 m-0" style="min-width: 250px;">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0">
            <i class="fas fa-search text-muted"></i>
        </span>
        <input type="text"
               class="form-control border-start-0 ps-0 "
               placeholder="Jump to employee..."
               wire:model.live.debounce.300ms="search"
        >
    </div>
    
    @if(!empty($results))
        <div class="dropdown-menu show position-absolute w-100 mt-1" style="max-height: 300px; overflow-y: auto;">
            @foreach($results as $result)
                <button class="dropdown-item" wire:click="selectEmployee({{ $result['id'] }})" type="button">
                    {{ $result['label'] }}
                </button>
            @endforeach
        </div>
    @endif
</div>