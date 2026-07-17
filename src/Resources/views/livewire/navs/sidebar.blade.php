<div
    class="sidebar-container bg-light border-end d-flex flex-column align-items-stretch
            @if ($state === 'full') sidebar-full
            @else sidebar-icon @endif">
    <ul class="nav flex-column mt-3">
        @foreach ($headerItems as $item)
            @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
        @endforeach
        @foreach ($items as $item)
            @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
        @endforeach
        @foreach ($footerItems as $item)
            @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
        @endforeach
    </ul>

    {{-- Settings Link — sits directly above the bottom toolbar --}}
    @if($settingsContext)
    <ul class="nav flex-column mt-auto mb-3" style="background: inherit;">
        <li>
            <a href="#"
               wire:click.prevent="openSettings"
               class="nav-link d-flex align-items-center py-1 text-muted"
               data-bs-toggle="tooltip"
               wire:ignore.self
               title="{{ $settingsContext }} Settings">
                <i class="fas fa-cog opacity-6 me-2" aria-hidden="true"></i>
                <span class="nav-link-text ms-1">{{ ucfirst($settingsContext) }} Settings</span>
            </a>
        </li>
    </ul>
    @endif

    <div class="p-2 border-top d-flex justify-content-between align-items-center">
        <button wire:click="toggleState" class="btn btn-sm btn-outline-secondary px-3 me-2" title="Toggle sidebar width">
            <i
                class="fa fa-chevron-left toggle-icon @if ($state === 'full') rotated-left @else rotated-right @endif"></i>
        </button>
        @if ($allowTypeSwitch)
            <button wire:click="switchToHorizontal" class="btn btn-sm btn-outline-secondary px-3 ms-2"
                title="Switch to horizontal menu">
                <i class="fa fa-arrows-alt-v"></i>
                @if ($state === 'full')
                    <span>Horizontal</span>
                @endif
            </button>
        @endif
    </div>


    <style>
        /* Sidebar container core styles */
        .sidebar-container {
            flex-shrink: 0;
            /* prevent shrinking in flex layout */
            transition: width 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
            position: sticky;
            top: 55px;
            /* height of top bar */
            height: calc(100vh - 60px);
            padding-bottom: 0.1rem;
        }

        /* Width states */
        .sidebar-full {
            width: 220px;
        }

        .sidebar-icon {
            width: 60px;
        }

        /* Hide text labels in icon mode */
        .sidebar-icon .nav-link span {
            display: none;
        }

        /* Toggle icon rotation */
        .toggle-icon {
            transition: transform 0.3s ease;
        }

        .rotated-left {
            transform: rotate(0deg);
        }

        .rotated-right {
            transform: rotate(180deg);
        }
    </style>
</div>
