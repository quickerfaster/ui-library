<div class="settings-panel mx-auto" style="max-width: 720px;">
    <!-- Top Tabs -->
    <div class="nav nav-pills mb-3" role="tablist">
        @foreach($groups as $groupKey => $group)
            <button wire:click="setActiveGroup('{{ $groupKey }}')"
                    class="nav-link {{ $activeGroup === $groupKey ? 'active bg-primary text-white' : 'text-dark' }}">
                @if(isset($group['icon']))
                    <i class="{{ $group['icon'] }} me-1"></i>
                @endif
                {{ $group['label'] }}
            </button>
        @endforeach
    </div>

    <!-- Settings Content -->
    <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="card-title mb-4">{{ $groups[$activeGroup]['label'] ?? '' }}</h4>

                    @foreach($currentGroupSettings as $setting)
                        @php
                            $key = $setting['key'];
                            $effective = $effectiveValues[$key] ?? $setting['default'] ?? null;
                            $override = $overrides[$key] ?? null;
                            $inheritedFrom = $inheritance[$key] ?? 'system';
                            $isOverridden = $override !== null;
                            $type = $setting['type'] ?? 'text';
                            $options = $setting['options'] ?? [];
                            // If options is a string like 'timezones', we could resolve dynamically
                            if (is_string($options) && $options === 'timezones') {
                                $options = timezone_identifiers_list();
                                $options = array_combine($options, $options);
                            }
                            $slugKey = \Illuminate\Support\Str::slug($key);
                        @endphp

                        <div class="mb-4 pb-3 border-bottom" wire:key="setting-{{ $key }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1 me-3">
                                    <label class="form-label fw-bold">{{ $setting['label'] }}</label>
                                    @if(!empty($setting['help']))
                                        <div class="form-text mb-2">{{ $setting['help'] }}</div>
                                    @endif
                                    @if($type === 'select')
                                        <select wire:model="overrides.{{ $key }}" class="form-select">
                                            @foreach($options as $optValue => $optLabel)
                                                <option value="{{ $optValue }}" {{ ($override ?? $effective) == $optValue ? 'selected' : '' }}>
                                                    {{ $optLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif($type === 'number')
                                        <input type="number" wire:model="overrides.{{ $key }}"
                                               class="form-control"
                                               min="{{ $setting['min'] ?? '' }}"
                                               max="{{ $setting['max'] ?? '' }}">
                                    @else
                                        <input type="text" wire:model="overrides.{{ $key }}" class="form-control"
                                               id="setting-{{ $slugKey }}"
                                               wire:key="input-{{ $slugKey }}">
                                    @endif

                                    {{-- Pattern Helper Insert Buttons (Livewire) --}}
                                    @if($setting['pattern_helpers'] ?? false)
                                    <div class="mt-2 d-flex flex-wrap" style="gap: 2px;">
                                        <button type="button" class="btn btn-outline-secondary btn-xs"
                                                style="padding: 1px 5px; font-size: 0.7rem; line-height: 1.2;"
                                                wire:click="insertPatternPlaceholder('{{ $key }}', '{year}')"
                                                title="Full year (2026)">{year}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs"
                                                style="padding: 1px 5px; font-size: 0.7rem; line-height: 1.2;"
                                                wire:click="insertPatternPlaceholder('{{ $key }}', '{year:2}')"
                                                title="Short year (26)">{year:2}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs"
                                                style="padding: 1px 5px; font-size: 0.7rem; line-height: 1.2;"
                                                wire:click="insertPatternPlaceholder('{{ $key }}', '{month}')"
                                                title="Month number (07)">{month}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs"
                                                style="padding: 1px 5px; font-size: 0.7rem; line-height: 1.2;"
                                                wire:click="insertPatternPlaceholder('{{ $key }}', '{day}')"
                                                title="Day number (16)">{day}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs"
                                                style="padding: 1px 5px; font-size: 0.7rem; line-height: 1.2;"
                                                wire:click="insertPatternPlaceholder('{{ $key }}', '{sequence:5}')"
                                                title="5-digit sequence (00001)">{sequence:5}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs"
                                                style="padding: 1px 5px; font-size: 0.7rem; line-height: 1.2;"
                                                wire:click="insertPatternPlaceholder('{{ $key }}', '{sequence}')"
                                                title="Default sequence">{sequence}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs"
                                                style="padding: 1px 5px; font-size: 0.7rem; line-height: 1.2;"
                                                wire:click="insertPatternPlaceholder('{{ $key }}', '{id}')"
                                                title="Record ID">{id}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs"
                                                style="padding: 1px 5px; font-size: 0.7rem; line-height: 1.2;"
                                                wire:click="insertPatternPlaceholder('{{ $key }}', '-')"
                                                title="Dash separator">-</button>
                                    </div>
                                    @endif

                                    {{-- Pattern Preview (Livewire-powered) --}}
                                    @if(($setting['pattern_preview'] ?? false) && ($setting['pattern_helpers'] ?? false))
                                    <div class="mt-2 d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                wire:click="previewPattern('{{ $key }}')">
                                            <i class="fas fa-eye me-1"></i> Preview
                                        </button>
                                        @if(isset($patternPreviews[$key]))
                                        <code class="bg-light px-2 py-1 rounded">{{ $patternPreviews[$key] }}</code>
                                        @endif
                                    </div>
                                    @endif

                                    <div class="form-text mt-1">
                                        <small>
                                            @if($isOverridden)
                                                <span class="text-warning">
                                                    <i class="fas fa-pencil-alt"></i> Overridden (current: {{ $effective }})
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    <i class="fas fa-sitemap"></i> Inherited from {{ ucfirst($inheritedFrom) }}
                                                </span>
                                            @endif
                                        </small>
                                    </div>
                                </div>

                                <div class="btn-group-vertical">
                                    @if($isOverridden)
                                        <button wire:click="resetSetting('{{ $key }}')"
                                                class="btn btn-sm btn-outline-danger mb-1"
                                                title="Reset to default">
                                            <i class="fas fa-undo-alt"></i>
                                        </button>
                                    @endif
                                    <button wire:click="saveSetting('{{ $key }}')"
                                            class="btn btn-sm btn-primary"
                                            title="Save">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
</div>
