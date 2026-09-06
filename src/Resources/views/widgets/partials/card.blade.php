@php
    // Shared polished card wrapper. Accepts:
    //   $data   - the processed widget array (title, icon, color, ...)
    //   $header - optional array: ['title', 'description', 'icon', 'color', 'actions']
    //   $body   - pre-rendered card body HTML
    //   $hover  - whether to add the transition-hover lift (for clickable cards)
    $hover = $hover ?? false;
    $header = $header ?? [];
    $body = $body ?? '';
@endphp
<div class="card h-100 border-0 shadow-sm rounded-4 {{ $hover ? 'transition-hover' : '' }}">
    @if(!empty($header))
        <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
            <div class="d-flex align-items-center">
                @if(!empty($header['icon']))
                    <div class="icon-shape icon-md rounded-3 bg-gradient-{{ $header['color'] ?? 'primary' }} text-white d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; flex-shrink: 0;">
                        <i class="fa-solid {{ $header['icon'] }}"></i>
                    </div>
                @endif
                <div class="min-width-0">
                    @if(!empty($header['title']))
                        <h6 class="fw-bolder mb-0 text-body">{{ $header['title'] }}</h6>
                    @endif
                    @if(!empty($header['description']))
                        <p class="text-sm text-secondary mb-0">{{ $header['description'] }}</p>
                    @endif
                </div>
                @if(!empty($header['actions']))
                    <div class="ms-auto">{!! $header['actions'] !!}</div>
                @endif
            </div>
        </div>
    @endif
    {!! $body !!}
</div>
