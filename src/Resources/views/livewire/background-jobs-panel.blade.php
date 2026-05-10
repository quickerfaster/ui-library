<div>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button class="nav-link {{ $activeTab === 'exports' ? 'active' : '' }}" wire:click="switchTab('exports')">
                <i class="fas fa-download"></i> Exports
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $activeTab === 'imports' ? 'active' : '' }}" wire:click="switchTab('imports')">
                <i class="fas fa-upload"></i> Imports
            </button>
        </li>
    </ul>

    <div>
        @if ($activeTab === 'exports')
            @livewire('qf.recent-exports', ['embedded' => true], key('exports-'.now()))
        @else
            @livewire('qf.recent-imports', ['embedded' => true], key('imports-'.now()))
        @endif
    </div>
</div>