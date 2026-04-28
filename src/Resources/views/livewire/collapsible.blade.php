<div>
    <div class="collapsible-content" style="display: {{ $isOpen ? 'block' : 'none' }}; padding: 1rem 0;">
        @if($isOpen && $component)
            <div class="card border-0 bg-light p-3">
                @if($title)
                    <div class="border-bottom pb-2 mb-3">
                        <h6 class="text-muted mb-0">{{ $title }}</h6>
                    </div>
                @endif
                @livewire($component, $componentParams, key($collapsibleId . '-' . $component))
            </div>
        @endif
    </div>
</div>