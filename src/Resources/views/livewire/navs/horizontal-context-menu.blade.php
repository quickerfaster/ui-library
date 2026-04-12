<nav class="navbar navbar-expand navbar-light bg-light shadow-sm mb-3"
     @if($position === 'right') style="justify-content: flex-end;" @endif>
    <div class="container-fluid {{ $position === 'right' ? 'justify-content-end' : '' }}">
        <ul class="navbar-nav {{ $position === 'right' ? '' : 'me-auto' }}">
            @foreach ($items as $item)
                <li class="nav-item">
                    <a href="{{ $item['route'] ?? '#' }}" class="nav-link" wire:navigate>
                        @if (!empty($item['icon']))
                            <i class="{{ $item['icon'] }} me-1"></i>
                        @endif
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach

            @if ($allowTypeSwitch)
                <li class="nav-item ms-2">
                    <button wire:click="switchToSidebar" class="btn btn-sm btn-outline-secondary"
                            title="Switch to sidebar">
                        <i class="fa fa-bars-staggered"></i>
                        <span class="d-none d-md-inline">Sidebar</span>
                    </button>
                </li>
            @endif
        </ul>
    </div>
</nav>