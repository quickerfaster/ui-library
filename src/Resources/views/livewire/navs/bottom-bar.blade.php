<nav class="navbar navbar-light bg-white shadow-sm d-md-none fixed-bottom" style="z-index: 1030;">
    <div class="d-flex px-2 py-1" style="gap:.5rem; overflow-x:auto; overflow-y:visible;">

        @php
            $visibleItems = array_slice($items, 0, $maxVisible);
            $overflowItems = array_slice($items, $maxVisible);
        @endphp

        @foreach ($visibleItems as $item)
            <a href="{{ $item['route'] ?? '#' }}"
               class="btn btn-light flex-shrink-0 text-center"
               style="min-width:70px;"
               wire:navigate>
                @if(!empty($item['icon']))
                    <i class="{{ $item['icon'] }} d-block mb-1"></i>
                @endif
                <small>{{ $item['label'] }}</small>
            </a>
        @endforeach

        @if (count($overflowItems) > 0)
            <div class="btn-group dropup flex-shrink-0 d-md-block d-none">
                <button class="btn btn-light dropdown-toggle"
                        data-bs-toggle="dropdown"
                        data-bs-display="static"
                        data-bs-boundary="viewport">
                    <i class="fa fa-ellipsis-h"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach ($overflowItems as $item)
                        <li>
                            <a href="{{ $item['route'] ?? '#' }}" class="dropdown-item d-flex align-items-center" wire:navigate>
                                <i class="fa {{ $item['icon'] }} me-2"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Mobile bottom sheet --}}
            <div class="d-md-none">
                <button class="btn btn-light" data-bs-toggle="offcanvas" data-bs-target="#mobileMoreSheet">
                    <i class="fa fa-ellipsis-h"></i>
                </button>
                <div class="offcanvas offcanvas-bottom" tabindex="-1" id="mobileMoreSheet">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title">More</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                    </div>
                    <div class="offcanvas-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach ($overflowItems as $item)
                                <li class="list-group-item">
                                    <a href="{{ $item['route'] ?? '#' }}" class="d-flex align-items-center" wire:navigate>
                                        <i class="fa {{ $item['icon'] }} me-2"></i>
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

    </div>
</nav>