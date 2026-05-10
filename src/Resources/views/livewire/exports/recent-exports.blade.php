<div wire:poll.10s="loadExports" class="{{ $embedded ? '' : 'dropdown d-inline-block' }}">
    @if (!$embedded)
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" wire:click="toggleDropdown" type="button">
            <i class="fas fa-download"></i> Exports
            @if ($this->inProgressCount)
                <span class="badge bg-info ms-1">
                    <i class="fas fa-spinner fa-spin"></i> {{ $this->inProgressCount }}
                </span>
            @endif
            @if ($completedExports->where('status', 'completed')->count())
                <span class="badge bg-primary ms-1">{{ $completedExports->where('status', 'completed')->count() }}</span>
            @endif
        </button>
    @endif

    @if (!$embedded && $dropdownOpen)
        <ul class="dropdown-menu dropdown-menu-end show" style="min-width: 320px;" wire:click.away="closeDropdown">

            {{-- In Progress Section --}}
            @if ($inProgressExports->count())
                <li class="dropdown-header text-muted">
                    <i class="fas fa-hourglass-half me-1"></i> In Progress
                </li>
                @foreach ($inProgressExports as $export)
                    <li>
                        <div class="dropdown-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-spinner fa-spin text-info me-2"></i>
                                {{ $export->created_at->diffForHumans() }}
                                <span
                                    class="badge bg-secondary">{{ $export->status === 'processing' ? 'Processing' : 'Queued' }}</span>
                            </div>
                            <button wire:click="cancelExport({{ $export->id }})"
                                class="btn btn-sm btn-link text-danger" title="Cancel export">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </li>
                @endforeach
                @if ($completedExports->count())
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                @endif
            @endif

            {{-- Completed / Failed Section --}}
            @forelse($completedExports as $export)
                <li>
                    @if ($export->status === 'completed')
                        <a class="dropdown-item d-flex justify-content-between align-items-center"
                            href="{{ route('export.download', ['token' => $export->download_token]) }}">
                            <div>
                                <i
                                    class="fas {{ $export->format === 'pdf' ? 'fa-file-pdf' : ($export->format === 'csv' ? 'fa-file-csv' : 'fa-file-excel') }} me-2"></i>
                                {{ $export->created_at->format('Y-m-d H:i') }}
                                <span class="small text-muted">({{ number_format($export->file_size / 1024, 1) }}
                                    KB)</span>
                            </div>
                            <i class="fas fa-download text-success"></i>
                        </a>
                    @else
                        <div class="dropdown-item d-flex justify-content-between align-items-center text-muted">
                            <div>
                                <i class="fas fa-times-circle text-danger me-2"></i>
                                {{ $export->created_at->format('Y-m-d H:i') }}
                            </div>
                            <span class="badge bg-danger">Failed</span>
                        </div>
                    @endif
                </li>
            @empty
                @if (!$inProgressExports->count())
                    <li><span class="dropdown-item text-muted">No recent exports</span></li>
                @endif
            @endforelse

            {{-- Clear All Button --}}
            @if ($completedExports->count())
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item text-danger" href="#" wire:click.prevent="confirmClearAllExports">
                        <i class="fas fa-trash-alt me-2"></i> Clear all
                    </a>
                </li>
            @endif
        </ul>
    @endif

    @if ($embedded)
        {{-- Embedded list (no dropdown wrapper) --}}
        <div class="bg-jobs-list">
            {{-- In Progress Section --}}
            @if ($inProgressExports->count())
                <div class="mb-3">
                    <h6 class="text-muted"><i class="fas fa-hourglass-half me-1"></i> In Progress</h6>
                    @foreach ($inProgressExports as $export)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <i class="fas fa-spinner fa-spin text-info me-2"></i>
                                {{ $export->created_at->diffForHumans() }}
                                <span class="badge bg-secondary">{{ $export->status === 'processing' ? 'Processing' : 'Queued' }}</span>
                            </div>
                            <button wire:click="cancelExport({{ $export->id }})" class="btn btn-sm btn-link text-danger" title="Cancel export">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Completed / Failed Section --}}
            @if ($completedExports->count())
                <div>
                    <h6 class="text-muted"><i class="fas fa-history me-1"></i> Recent</h6>
                    @foreach ($completedExports as $export)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            @if ($export->status === 'completed')
                                <a href="{{ route('export.download', ['token' => $export->download_token]) }}" class="text-decoration-none flex-grow-1">
                                    <i class="fas {{ $export->format === 'pdf' ? 'fa-file-pdf' : ($export->format === 'csv' ? 'fa-file-csv' : 'fa-file-excel') }} me-2"></i>
                                    {{ $export->created_at->format('Y-m-d H:i') }}
                                    <span class="small text-muted">({{ number_format($export->file_size / 1024, 1) }} KB)</span>
                                </a>
                            @else
                                <div class="flex-grow-1">
                                    <i class="fas fa-times-circle text-danger me-2"></i>
                                    {{ $export->created_at->format('Y-m-d H:i') }}
                                    <span class="badge bg-danger">Failed</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                    <div class="mt-2 text-end">
                        <button wire:click="confirmClearAllExports" class="btn btn-sm btn-link text-danger">
                            <i class="fas fa-trash-alt me-1"></i> Clear all
                        </button>
                    </div>
                </div>
            @elseif (!$inProgressExports->count())
                <div class="text-muted text-center py-3">No recent exports</div>
            @endif
        </div>
    @endif
</div>