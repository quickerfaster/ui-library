<style>
    .context-sheet-item:hover {
        background-color: #f8f9fa;
    }
    .context-sheet-item:hover .text-muted {
        color: #6c757d !important;
    }
    .context-sheet-item:hover .opacity-25 {
        opacity: 0.5 !important;
    }
</style>
@if ($isOpen)
<div class="d-md-none"
     style="position: fixed; inset: 0; z-index: 1050;"
     wire:click.self="close">

    {{-- Backdrop --}}
    <div class="position-absolute bg-dark opacity-25" style="inset: 0;"
         wire:click="close"></div>

    {{-- Sheet --}}
    <div class="position-absolute bottom-0 start-0 end-0 bg-white shadow-lg"
         style="max-height: 60vh; border-radius: 16px 16px 0 0; overflow-y: auto; padding-bottom: env(safe-area-inset-bottom);">

        {{-- Drag handle --}}
        <div class="d-flex justify-content-center pt-2 pb-1">
            <div class="bg-secondary rounded-pill opacity-50" style="width: 36px; height: 4px;"></div>
        </div>

        {{-- Header --}}
        <div class="d-flex align-items-center px-3 py-2 border-bottom">
            @if ($contextIcon)
                <i class="{{ $contextIcon }} me-2 text-primary fs-5"></i>
            @endif
            <h6 class="mb-0 fw-bold flex-grow-1">{{ $contextLabel }}</h6>
            <button class="btn btn-sm btn-light rounded-circle p-0 d-flex align-items-center justify-content-center"
                    wire:click="close" style="width: 32px; height: 32px; min-width: 32px;">
                <i class="fas fa-times opacity-50"></i>
            </button>
        </div>

        {{-- Sub-items --}}
        <div class="pb-3">
            @forelse ($items as $item)
                @php
                    $itemUrl = $this->resolveItemUrl($item);
                    $isActive = $this->isItemActive($item);
                @endphp
                <a href="{{ $itemUrl }}"
                   wire:click="navigateToItem('{{ $itemUrl }}')"
                   class="d-flex align-items-center px-3 py-3 text-decoration-none context-sheet-item
                          {{ $isActive ? 'fw-bold' : 'text-dark' }}"
                   @if ($isActive)
                   style="background: rgba(13, 110, 253, 0.25); border-left: 3px solid #0d6efd; color: #212529;"
                   @else
                   style="border-left: 3px solid transparent;"
                   @endif>
                    @if (!empty($item['icon']))
                        <i class="{{ $item['icon'] }} me-3 {{ $isActive ? 'text-primary' : 'text-muted' }}" style="width: 20px; text-align: center;"></i>
                    @endif
                    <span class="flex-grow-1">{{ $item['label'] }}</span>
                    @if ($isActive)
                        <i class="fas fa-check text-primary"></i>
                    @else
                        <i class="fas fa-chevron-right text-muted opacity-25"></i>
                    @endif
                </a>
            @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-folder-open d-block mb-2 fs-3 opacity-25"></i>
                    <small>No items in this section</small>
                </div>
            @endforelse
        </div>

    </div>
</div>
@endif