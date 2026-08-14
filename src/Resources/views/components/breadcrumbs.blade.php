<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        @foreach($visibleSegments() as $i => $segment)
            @if($shouldCollapse() && $i === 1)
                <li class="breadcrumb-item breadcrumb-collapse">
                    <button type="button"
                        class="breadcrumb-collapse-toggle"
                        data-breadcrumb-toggle
                        aria-haspopup="true"
                        aria-expanded="false">...</button>

                    <div class="breadcrumb-collapse-menu" data-breadcrumb-menu>
                        @foreach($hiddenSegments() as $hidden)
                            <a href="{{ $hidden['url'] ?? '#' }}">{{ $hidden['label'] }}</a>
                        @endforeach
                    </div>
                </li>
            @endif

            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" @if($loop->last) aria-current="page" @endif>
                @if($loop->last)
                    {{ $segment['label'] }}
                @else
                    <a href="{{ $segment['url'] ?? '#' }}">{{ $segment['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
