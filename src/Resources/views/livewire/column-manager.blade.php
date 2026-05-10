<div>
    <div class="mb-3 p-3">
        <h6 class="mb-2">Visible columns</h6>
        <div class="list-group ps-4 ">
@foreach ($allColumns as $column)
    @php 
        $def = $columns[$column]; 
        $isVisible = in_array($column, $visibleColumns);
    @endphp
    <div wire:key="col-{{ $column }}-{{ $isVisible ? 'on' : 'off' }}">
        <div class="list-group-item d-flex align-items-center border-0 px-0">
            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       wire:click="toggleColumn('{{ $column }}')"
                       id="col-{{ $column }}" @checked($isVisible)>
                <label class="form-check-label" for="col-{{ $column }}">
                    {{ $def['label'] ?? ucfirst($column) }}
                </label>
            </div>
        </div>
    </div>
@endforeach
        </div>
    </div>
    @if ($this->isResetVisible())
        <div class="mt-3">
            <button class="btn btn-sm btn-outline-secondary" wire:click="resetColumns">
                <i class="fas fa-undo-alt me-2"></i> Reset to default
            </button>
        </div>
    @endif
</div>