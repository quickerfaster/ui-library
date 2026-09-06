<div class="monthly-view">
    @php
        use Carbon\Carbon;

        // Group records by month (YYYY-MM) based on start_date
        $grouped = $records->groupBy(function ($record) use ($viewConfig) {
            $dateField = $viewConfig['dateField'] ?? 'start_date';
            $date = data_get($record, $dateField);
            if ($date instanceof Carbon) {
                return $date->format('Y-m');
            }
            return Carbon::parse($date)->format('Y-m');
        });

        // Sort months chronologically
        $months = $grouped->keys()->sort();

        // Status → color mapping
        $statusColors = $viewConfig['badgeColors'] ?? [
            'Approved' => 'success',
            'Pending' => 'warning',
            'Draft' => 'info',
            'Denied' => 'danger',
            'Cancelled' => 'secondary',
        ];
    @endphp

    @forelse($months as $month)
        @php
            $monthRecords = $grouped[$month];
            $monthDate = Carbon::createFromFormat('Y-m', $month);
            $monthName = $monthDate->format('F Y');
        @endphp

        <div class="month-section mb-4">
            {{-- Month Header --}}
            <div class="month-header d-flex align-items-center mb-3">
                <div class="month-indicator rounded-circle d-flex align-items-center justify-content-center me-3"
                    style="width: 42px; height: 42px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <span class="text-white fw-bold small">{{ $monthDate->format('M') }}</span>
                </div>
                <h5 class="fw-bold mb-0 text-dark">{{ $monthName }}</h5>
                <span class="badge bg-light text-muted ms-3 rounded-pill px-3 py-1">
                    {{ $monthRecords->count() }} {{ Str::plural('request', $monthRecords->count()) }}
                </span>
            </div>

            {{-- Month Records as Timeline Cards --}}
            <div class="month-records ps-4 border-start border-2" style="border-color: #e5e7eb !important;">
                @foreach($monthRecords as $record)
                    @php
                        $dateField = $viewConfig['dateField'] ?? 'start_date';
                        $endDateField = $viewConfig['endDateField'] ?? 'end_date';

                        $startDate = data_get($record, $dateField);
                        $endDate = data_get($record, $endDateField);

                        if ($startDate instanceof Carbon) {
                            $startFormatted = $startDate->format('M j, Y');
                            $startDay = $startDate->format('j');
                            $startDayLabel = $startDate->format('D');
                        } else {
                            $startFormatted = Carbon::parse($startDate)->format('M j, Y');
                            $startDay = Carbon::parse($startDate)->format('j');
                            $startDayLabel = Carbon::parse($startDate)->format('D');
                        }

                        if ($endDate instanceof Carbon) {
                            $endFormatted = $endDate->format('M j, Y');
                            $endDay = $endDate->format('j');
                        } else {
                            $endFormatted = Carbon::parse($endDate)->format('M j, Y');
                            $endDay = Carbon::parse($endDate)->format('j');
                        }

                        // Status badge
                        $badgeField = $viewConfig['badgeField'] ?? 'status';
                        $val = data_get($record, $badgeField);
                        $color = $statusColors[$val] ?? 'secondary';

                        // Title
                        $titleParts = [];
                        foreach (($viewConfig['titleFields'] ?? []) as $field) {
                            $titleParts[] = $this->getValueFromRecord($record, $field);
                        }

                        // Subtitle
                        $subtitleParts = [];
                        foreach (($viewConfig['subtitleFields'] ?? []) as $field) {
                            if ($field === $badgeField) continue; // skip badge field — shown as badge
                            $subtitleParts[] = $this->getValueFromRecord($record, $field);
                        }

                        $isMultiDay = ($startFormatted !== $endFormatted);
                    @endphp

                    <div class="timeline-item position-relative pb-4"
                        wire:key="monthly-{{ $record->id }}"
                        onclick="if(!event.target.closest('.stop-propagation')) { window.location='{{ $this->getShowUrl($record->id) }}' }"
                        style="cursor: pointer;">

                        {{-- Timeline dot --}}
                        <div class="timeline-dot position-absolute rounded-circle border border-2 border-white shadow-sm"
                            style="
                                left: -1.3rem;
                                top: 0.15rem;
                                width: 14px;
                                height: 14px;
                                background: var(--bs-{{ $color }});
                                z-index: 2;
                            ">
                        </div>

                        {{-- Card --}}
                        <div class="card border-0 shadow-sm hover-lift transition-all ms-3"
                            style="border-radius: 10px;">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start justify-content-between">
                                    {{-- Date Block (Left) --}}
                                    <div class="d-flex align-items-center me-3" style="min-width: 80px;">
                                        <div class="text-center">
                                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.05em;">
                                                {{ $startDayLabel }}
                                            </div>
                                            <div class="fw-bold {{ $color === 'danger' ? 'text-danger' : 'text-dark' }}" style="font-size: 1.4rem; line-height: 1;">
                                                {{ $startDay }}
                                            </div>
                                        </div>
                                        @if ($isMultiDay)
                                            <div class="mx-2 text-muted">
                                                <i class="fas fa-arrow-right small"></i>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.05em;">
                                                    {{ Carbon::parse($endDate)->format('D') }}
                                                </div>
                                                <div class="fw-bold {{ $color === 'danger' ? 'text-danger' : 'text-dark' }}" style="font-size: 1.4rem; line-height: 1;">
                                                    {{ $endDay }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Info Block (Center) --}}
                                    <div class="flex-grow-1 min-width-0">
                                        <h6 class="fw-bold mb-1 text-dark text-truncate">
                                            {{ implode(' · ', $titleParts) }}
                                        </h6>
                                        <div class="small text-muted mb-1">
                                            @if (!empty($subtitleParts))
                                                {{ implode(' • ', $subtitleParts) }}
                                            @endif
                                        </div>
                                        <div class="small text-muted">
                                            <i class="far fa-calendar-alt me-1 opacity-50"></i>
                                            {{ $startFormatted }}
                                            @if ($isMultiDay)
                                                <span class="mx-1">→</span>{{ $endFormatted }}
                                            @endif
                                            @php $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1; @endphp
                                            <span class="text-muted opacity-50">· {{ $days }} {{ Str::plural('day', $days) }}</span>
                                        </div>
                                    </div>

                                    {{-- Badge Block (Right) --}}
                                    @if ($val)
                                        <div class="ms-3 stop-propagation d-flex flex-column align-items-end">
                                            <span class="badge rounded-pill bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }} px-2 py-1 mb-2"
                                                style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.02em;">
                                                {{ $val }}
                                            </span>

                                            {{-- Row actions --}}
                                            <div class="op-2-hover">
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
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    @empty
        <div class="text-center py-5 text-muted">
            <i class="fas fa-calendar-alt fa-3x mb-3 opacity-25"></i>
            <h6 class="fw-normal">No leave requests found.</h6>
            <p class="small">Your leave history will appear here once you submit a request.</p>
        </div>
    @endforelse
</div>

<style>
    .monthly-view .timeline-dot {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .monthly-view .timeline-item:hover .timeline-dot {
        transform: scale(1.4);
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2) !important;
    }
    .hover-lift {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .hover-lift:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08) !important;
    }
    .op-2-hover {
        opacitY: 0.2;
        transition: opacity 0.15s ease;
    }
    .timeline-item:hover .op-2-hover {
        opacity: 1;
    }
</style>