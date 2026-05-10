<div wire:poll.10s="loadImports" class="{{ $embedded ? '' : 'dropdown d-inline-block' }}">
    @if (!$embedded)
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" wire:click="toggleDropdown" type="button">
            <i class="fas fa-upload"></i> Imports
            @if ($this->inProgressCount)
                <span class="badge bg-warning ms-1">
                    <i class="fas fa-spinner fa-spin"></i> {{ $this->inProgressCount }}
                </span>
            @endif
            @if ($recentImports->where('status', 'completed')->count())
                <span class="badge bg-primary ms-1">{{ $recentImports->where('status', 'completed')->count() }}</span>
            @endif
        </button>
    @endif

    @if (!$embedded && $dropdownOpen)
        <ul class="dropdown-menu dropdown-menu-end show" style="min-width: 320px;" wire:click.away="closeDropdown">

            {{-- In Progress Section --}}
            @if ($inProgressImports->count())
                <li class="dropdown-header text-muted">
                    <i class="fas fa-hourglass-half me-1"></i> In Progress
                </li>
                @foreach ($inProgressImports as $import)
                    <li>
                        <div class="dropdown-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-spinner fa-spin text-info me-2"></i>
                                {{ $import->created_at->diffForHumans() }}
                                <span class="badge bg-secondary">{{ ucfirst($import->status) }}</span>
                            </div>
                            <button wire:click="cancelImport({{ $import->id }})"
                                    class="btn btn-sm btn-link text-danger"
                                    title="Cancel import">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </li>
                @endforeach
                @if ($recentImports->count())
                    <li><hr class="dropdown-divider"></li>
                @endif
            @endif

            {{-- Completed / Failed Section --}}
            @forelse($recentImports as $import)
                <li>
                    <div class="dropdown-item d-flex justify-content-between align-items-center">
                        <div>
                            @if ($import->status === 'completed')
                                <i class="fas fa-check-circle text-success me-2"></i>
                            @else
                                <i class="fas fa-times-circle text-danger me-2"></i>
                            @endif
                            <strong>{{ $import->created_at->format('Y-m-d H:i') }}</strong>
                            <small class="text-muted">
                                {{ $import->successful_rows ?? 0 }} imported
                                @if ($import->failed_rows > 0)
                                    , {{ $import->failed_rows }} failed
                                @endif
                            </small>
                        </div>
                        @if ($import->status === 'completed' && $import->error_file)
                            <a href="{{ route('import.download-errors', $import) }}"
                               class="btn btn-sm btn-outline-danger"
                               target="_blank" title="Download error report">
                                <i class="fas fa-download"></i>
                            </a>
                        @endif
                    </div>
                </li>
            @empty
                @if (!$inProgressImports->count())
                    <li><span class="dropdown-item text-muted">No recent imports</span></li>
                @endif
            @endforelse

            @if ($recentImports->count())
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="#" wire:click.prevent="confirmClearAll">
                        <i class="fas fa-trash-alt me-2"></i> Clear all
                    </a>
                </li>
            @endif
        </ul>
    @endif

    @if ($embedded)
        <div class="bg-jobs-list">
            {{-- In Progress Section --}}
            @if ($inProgressImports->count())
                <div class="mb-3">
                    <h6 class="text-muted"><i class="fas fa-hourglass-half me-1"></i> In Progress</h6>
                    @foreach ($inProgressImports as $import)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <i class="fas fa-spinner fa-spin text-info me-2"></i>
                                {{ $import->created_at->diffForHumans() }}
                                <span class="badge bg-secondary">{{ ucfirst($import->status) }}</span>
                            </div>
                            <button wire:click="cancelImport({{ $import->id }})"
                                class="btn btn-sm btn-link text-danger" title="Cancel import">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Completed Section --}}
            @if ($recentImports->count())
                <div>
                    <h6 class="text-muted"><i class="fas fa-history me-1"></i> Recent</h6>
                    @foreach ($recentImports as $import)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                @if ($import->status === 'completed')
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                @else
                                    <i class="fas fa-times-circle text-danger me-2"></i>
                                @endif
                                <strong>{{ $import->created_at->format('Y-m-d H:i') }}</strong>
                                <small class="text-muted">
                                    {{ $import->successful_rows ?? 0 }} imported
                                    @if ($import->failed_rows > 0)
                                        , {{ $import->failed_rows }} failed
                                    @endif
                                </small>
                            </div>
                            @if ($import->status === 'completed' && $import->error_file)
                                <a href="{{ route('import.download-errors', $import) }}"
                                    class="btn btn-sm btn-outline-danger" target="_blank" title="Download error report">
                                    <i class="fas fa-download"></i>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-2 text-end">
                    <button wire:click="confirmClearAll" class="btn btn-sm btn-link text-danger">
                        <i class="fas fa-trash-alt me-1"></i> Clear all
                    </button>
                </div>
            @elseif (!$inProgressImports->count())
                <div class="text-muted text-center py-3">No recent imports</div>
            @endif
        </div>
    @endif
</div>
