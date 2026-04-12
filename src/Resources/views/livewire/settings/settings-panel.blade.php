<div class="settings-panel">
    <div class="row">
        <!-- Sidebar Tabs -->
        <div class="col-md-3">
            <div class="nav flex-column nav-pills" role="tablist">
                @foreach($groups as $groupKey => $group)
                    <button wire:click="setActiveGroup('{{ $groupKey }}')"
                            class="nav-link text-start {{ $activeGroup === $groupKey ? 'active bg-primary text-white' : 'text-dark' }}">
                        @if(isset($group['icon']))
                            <i class="{{ $group['icon'] }} me-2"></i>
                        @endif
                        {{ $group['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Settings Content -->
        <div class="col-md-9">
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
                        @endphp

                        <div class="mb-4 pb-3 border-bottom" wire:key="setting-{{ $key }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1 me-3">
                                    <label class="form-label fw-bold">{{ $setting['label'] }}</label>
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
                                        <input type="text" wire:model="overrides.{{ $key }}" class="form-control">
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
    </div>
</div>