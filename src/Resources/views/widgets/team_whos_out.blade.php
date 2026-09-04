@props(['data'])

@php
    ob_start();
@endphp

<div class="card-body p-3 p-lg-4">
    @if (empty($data['members']))
        <div class="text-center py-4">
            <div class="icon-shape icon-lg rounded-circle bg-gradient-{{ $data['color'] ?? 'warning' }} text-white d-inline-flex align-items-center justify-content-center mb-3"
                 style="width: 64px; height: 64px;">
                <i class="fa-solid fa-users fs-4"></i>
            </div>
            <p class="text-sm text-secondary mb-0">{{ $data['empty_state'] ?? 'Everyone is in today! 🎉' }}</p>
        </div>
    @else
        <div class="list-group list-group-flush">
            @foreach ($data['members'] as $member)
                <div class="list-group-item border-0 px-0 py-2 d-flex align-items-center">
                    {{-- Avatar / Photo --}}
                    <div class="me-3 flex-shrink-0">
                        @if (!empty($member['photo_url']))
                            <img src="{{ $member['photo_url'] }}"
                                 alt="{{ $member['name'] ?? 'Team member' }}"
                                 class="rounded-circle"
                                 style="width: 40px; height: 40px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-gradient-{{ $member['color'] ?? 'secondary' }} text-white d-flex align-items-center justify-content-center fw-bold"
                                 style="width: 40px; height: 40px; font-size: 0.85rem;">
                                {{ strtoupper(substr($member['name'] ?? '?', 0, 2)) }}
                            </div>
                        @endif
                    </div>

                    {{-- Name + Leave Info --}}
                    <div class="min-width-0 flex-grow-1">
                        <h6 class="mb-0 text-sm fw-bold text-body text-truncate">
                            {{ $member['name'] ?? 'Unknown' }}
                        </h6>
                        <p class="mb-0 text-xs text-secondary">
                            @if (!empty($member['leave_type']))
                                <span class="badge bg-{{ $member['leave_color'] ?? 'info' }} bg-opacity-10 text-{{ $member['leave_color'] ?? 'info' }} me-1">
                                    {{ $member['leave_type'] }}
                                </span>
                            @endif
                            @if (!empty($member['dates']))
                                {{ $member['dates'] }}
                            @endif
                        </p>
                    </div>

                    {{-- Optional: Return date --}}
                    @if (!empty($member['return_date']))
                        <div class="text-xs text-muted ms-2 text-end flex-shrink-0">
                            <small>Returns</small><br>
                            <strong>{{ $member['return_date'] }}</strong>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

@php
    $body = ob_get_clean();
@endphp

@include('qf::widgets.partials.card', ['data' => $data, 'body' => $body])