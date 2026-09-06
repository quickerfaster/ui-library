@props(['data'])

@php
    ob_start();
@endphp

<div class="card-body p-3 p-lg-4">
    @if (empty($data['actions']))
        <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
            <i class="fas fa-bolt fa-2x mb-2 opacity-50"></i>
            <p class="text-sm text-muted mb-0">No actions available yet.</p>
        </div>
    @else
        <div class="list-group list-group-flush">
            @foreach ($data['actions'] as $action)
                @php
                    $actionId = $action['id'] ?? $action['key'] ?? '';
                    $isFav = !empty($data['favorites']) && in_array($actionId, $data['favorites'], true);
                @endphp
                <div class="list-group-item list-group-item-action d-flex align-items-center border-0 px-2 py-2 rounded-3 transition-hover">
                    <a href="#"
                        wire:click.prevent="$dispatch('execute-quick-action', { id: '{{ $actionId }}' })"
                        class="d-flex align-items-center text-decoration-none flex-grow-1 min-width-0">
                        <span class="icon-shape icon-sm rounded-2 bg-gradient-{{ $data['color'] ?? 'warning' }} text-white d-inline-flex align-items-center justify-content-center me-3"
                            style="width: 36px; height: 36px; flex-shrink: 0;">
                            <i class="fa-solid {{ $action['icon'] ?? 'fas fa-bolt' }}"></i>
                        </span>
                        <span class="min-width-0">
                            <span class="d-block text-sm fw-semibold text-dark text-truncate">{{ $action['label'] }}</span>
                            @if (!empty($action['description']))
                                <span class="d-block text-xs text-muted text-truncate">{{ $action['description'] }}</span>
                            @endif
                        </span>
                    </a>
                    {{-- Star Toggle --}}
                    <button type="button"
                        class="btn btn-sm border-0 p-1 ms-1 {{ $isFav ? 'text-warning' : 'text-muted' }}"
                        wire:click.stop="$dispatch('toggle-favorite', { actionId: '{{ $actionId }}' })"
                        title="{{ $isFav ? 'Unpin' : 'Pin' }}"
                        style="flex-shrink: 0;">
                        <i class="{{ $isFav ? 'fas' : 'far' }} fa-star fa-sm"></i>
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>

@php
    $body = ob_get_clean();
@endphp

@include('qf::widgets.partials.card', [
    'data' => $data,
    'body' => $body,
    'header' => [
        'title' => $data['title'],
        'icon' => $data['icon'] ?? null,
        'color' => $data['color'] ?? 'warning',
        'description' => $data['description'] ?? null,
    ],
])
