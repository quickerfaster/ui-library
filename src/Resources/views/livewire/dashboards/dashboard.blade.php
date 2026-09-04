<div class="pt-3">
    @if(!empty($errorMessage))
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center" role="alert">
                    <i class="fa-solid fa-circle-exclamation fs-4 me-3"></i>
                    <div>
                        <strong class="d-block">Dashboard Error</strong>
                        <span>{{ $errorMessage }}</span>
                    </div>
                </div>
            </div>
        </div>
    @else
    {{--
        Optional hero banner. Dashboard config shape:
        'hero' => [
            'title'       => 'Welcome back!',
            'description' => 'A short overview of this dashboard.',
            'icon'        => 'fa-house-circle-check',
            'badge'       => 'QuickerFaster',   // optional
            'color'       => 'primary',         // gradient color, defaults to primary
        ],
    --}}
    @if(!empty($hero))
        <div class="row">
            <div class="col-12">
                <div class="card mb-4 border-0 shadow-lg overflow-hidden rounded-4">
                    <div class="card-body bg-gradient-{{ $hero['color'] ?? 'primary' }} p-4 p-md-5 position-relative">
                        <div class="row align-items-center">
                            <div class="col-12 col-lg-8">
                                @if(!empty($hero['badge']))
                                    <span class="badge bg-white text-{{ $hero['color'] ?? 'primary' }} text-uppercase text-xs mb-3 px-3 py-2">
                                        {{ $hero['badge'] }}
                                    </span>
                                @endif
                                @if(!empty($hero['title']))
                                    <h3 class="text-white fw-bolder mb-2">{{ $hero['title'] }}</h3>
                                @endif
                                @if(!empty($hero['description']))
                                    <p class="text-white opacity-8 mb-4 mb-lg-0 pe-lg-5">{{ $hero['description'] }}</p>
                                @endif
                            </div>
                            @if(!empty($hero['icon']))
                                <div class="col-12 col-lg-4 d-none d-lg-flex justify-content-end">
                                    <i class="fa-solid {{ $hero['icon'] }} text-white opacity-6" style="font-size: 6rem;"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($title || $description)
        <div class="dashboard-header mb-4 border-bottom-lg">
            @if($title)
                <h1 class="h3 mb-1">{{ $title }}</h1>
            @endif
            @include('qf::components.layouts.partials.company-title-suffix', ['asBadge' => true])
            @if($description)
                <p class="text-muted">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{--
        Optional stats row. Dashboard config shape:
        'stats' => [
            ['label' => 'Users', 'value' => 128, 'icon' => 'fa-users', 'color' => 'primary'],
        ],
    --}}
    @if(!empty($stats))
        <div class="row g-3 mb-4">
            @foreach($stats as $stat)
                <div class="col-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex align-items-center">
                                <div class="icon-shape icon-md rounded-3 bg-gradient-{{ $stat['color'] ?? 'primary' }} text-white me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; flex-shrink: 0;">
                                    <i class="fa-solid {{ $stat['icon'] ?? 'fa-chart-simple' }}"></i>
                                </div>
                                <div class="min-width-0">
                                    <p class="text-sm text-secondary mb-0 text-uppercase fw-semibold">{{ $stat['label'] ?? '' }}</p>
                                    <h5 class="fw-bolder mb-0">{{ is_numeric($stat['value'] ?? null) ? number_format($stat['value']) : ($stat['value'] ?? '') }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-{{ $layout['gutter'] ?? 3 }}">
        @foreach($widgetsData as $widget)
            <div class="col-md-{{ $widget['width'] }} ">
                @include('qf::widgets.' . $widget['type'], ['data' => $widget])
            </div>
        @endforeach
    </div>
</div>
    @endif
