<div class="list-view bg-white rounded-3 shadow-sm border overflow-hidden">
    @forelse($records as $record)
        @php
            $module = strtolower($this->getConfigResolver()->getModuleName());
            $modelPlural = \Str::plural(\Str::kebab($this->getConfigResolver()->getModelName()));
            $showUrl = url("/{$modelPlural}/{$record->id}" . (isset($returnParams) ? '?' . http_build_query($returnParams) : ''));
        @endphp

        <div class="list-group-item list-group-item-action border-0 border-bottom p-3 transition-all hover-bg-light position-relative" 
             wire:key="list-{{ $record->id }}" 
             {{-- Giant SaaS Trick: The entire row navigates, but we stop propagation on buttons --}}
             onclick="if(!event.target.closest('.stop-propagation')) { window.location='{{ $showUrl }}' }"
             style="cursor: pointer;">
            
            <div class="d-flex align-items-center">
                {{-- 1. Selection: Add .stop-propagation so checking doesn't open the record --}}
                @if($bulkSelection)
                    <div class="me-3 stop-propagation">
                        <input type="checkbox" class="form-check-input" wire:model.live="selectedRecords" value="{{ $record->id }}">
                    </div>
                @endif

                {{-- 2. Visual Hook --}}
                <div class="me-3 d-none d-md-block">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 40px; height: 40px;">
                        <span class="text-primary fw-bold small">{{ substr($this->getValueFromRecord($record, $viewConfig['titleFields'][0] ?? 'ID'), 0, 1) }}</span>
                    </div>
                </div>

                {{-- 3. Main Info Area --}}
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-center mb-0">
                        <h6 class="fw-bold mb-0 text-dark text-truncate">
                            @foreach($viewConfig['titleFields'] as $field)
                                <span>{{ $this->getValueFromRecord($record, $field) }}</span>
                                @if(!$loop->last) <span class="text-muted mx-1">·</span> @endif
                            @endforeach
                        </h6>

                        @if(!empty($viewConfig['badgeField']))
                            @php 
                                $val = $record->{$viewConfig['badgeField']};
                                $color = ($viewConfig['badgeColors'] ?? [])[$val] ?? 'secondary';
                            @endphp
                            <div class="stop-propagation">
                                <span class="badge rounded-pill bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }} px-2 py-1" style="font-size: 0.65rem; letter-spacing: 0.02em;">
                                    {{ $val ? 'ACTIVE' : 'INACTIVE' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Metadata --}}
                    <div class="d-flex align-items-center text-muted small mt-1">
                        @if(!empty($viewConfig['subtitleFields']))
                            @foreach($viewConfig['subtitleFields'] as $field)
                                <span class="text-truncate">{{ $this->getValueFromRecord($record, $field) }}</span>
                                @if(!$loop->last) <span class="mx-2">•</span> @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- 4. Action Area: Add .stop-propagation to prevent double-firing --}}
                <div class="ms-3 stop-propagation op-0-hover">
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
    @empty
        {{-- Empty state remains as defined before --}}
    @endforelse
</div>


<style>
.list-view {
    /* Main container border makes the whole list feel like a single unit */
    border: 1px solid #e5e7eb; 
    background: #ffffff;
    border-radius: 8px;
}

.list-group-item {
    /* Very light divider between rows */
    border-bottom: 1px solid #f3f4f6 !important; 
    background-color: transparent;
    transition: all 0.15s ease;
}

.list-group-item:last-child {
    border-bottom: none !important;
}

/* The 'Pro' Hover State */
.list-group-item:hover {
    /* Instead of just changing color, give it a tiny 'lift' */
    background-color: #f9fafb !important;
    border-left: 3px solid #0d6efd !important; /* Left indicator like Outlook/Slack */
    padding-left: calc(1rem - 3px) !important; /* Adjust padding to keep text aligned */
}


</style>
