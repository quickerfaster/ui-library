<div class="pt-3">
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

    <div class="row g-{{ $layout['gutter'] ?? 3 }}">
        @foreach($widgetsData as $widget)
            <div class="col-md-{{ $widget['width'] }} ">
                @include('qf::widgets.' . $widget['type'], ['data' => $widget])
            </div>
        @endforeach
    </div>
</div>
