@php
    use QuickerFaster\UILibrary\Services\Notifications\NotificationTypeRegistry;
@endphp

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-bell me-2"></i>Notifications
        </h4>
        <div class="d-flex gap-2">
            <select wire:model.live="typeFilter" class="form-select form-select-sm" style="width: auto;">
                <option value="">All Types</option>
                @foreach ($this->typeOptions as $type)
                    <option value="{{ $type }}">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <select wire:model.live="readFilter" class="form-select form-select-sm" style="width: auto;">
                <option value="all">All</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
            </select>
            <button wire:click="markAllAsRead" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-check-double me-1"></i>Mark All Read
            </button>
        </div>
    </div>

    @php $notifications = $this->notifications; @endphp

    @if ($notifications->isEmpty())
        <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
            <i class="fas fa-bell-slash fa-3x mb-3 opacity-50"></i>
            <p class="mb-0">No notifications found.</p>
        </div>
    @else
        <div class="list-group list-group-flush">
            @foreach ($notifications as $notification)
                <div class="list-group-item list-group-item-action border-bottom py-3 px-3 {{ $notification->isRead() ? 'bg-light' : '' }}"
                     wire:key="notification-{{ $notification->id }}">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="me-2">
                            <i class="{{ NotificationTypeRegistry::getIcon($notification->type) }} {{ NotificationTypeRegistry::getColor($notification->type) }} fs-5"></i>
                        </div>
                        <div class="flex-grow-1 me-2">
                            <h6 class="mb-1 fw-semibold text-sm">{{ $notification->subject }}</h6>
                            <p class="mb-1 text-xs text-muted">{{ \Illuminate\Support\Str::limit($notification->body, 150) }}</p>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                <span class="badge bg-light text-dark text-xs">{{ $notification->channel }}</span>
                                @if ($notification->isRead())
                                    <span class="badge bg-secondary text-xs">Read</span>
                                @else
                                    <span class="badge bg-primary text-xs">Unread</span>
                                @endif
                            </div>

                            {{-- Inline action buttons --}}
                            @if (!empty($notification->actions))
                                <div class="mt-2 d-flex gap-1 flex-wrap">
                                    @foreach ($notification->actions as $action)
                                        @php
                                            $style = $action['style'] ?? 'primary';
                                            $btnClass = match ($style) {
                                                'success' => 'btn-success',
                                                'danger' => 'btn-danger',
                                                'warning' => 'btn-warning',
                                                'info' => 'btn-info',
                                                'secondary' => 'btn-secondary',
                                                'dark' => 'btn-dark',
                                                'light' => 'btn-light',
                                                default => 'btn-primary',
                                            };
                                        @endphp
                                        <button class="btn btn-sm {{ $btnClass }}"
                                                wire:click="handleAction({{ $notification->id }}, '{{ $action['handler'] }}', {{ json_encode($action['data'] ?? []) }})">
                                            {{ $action['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if (! $notification->isRead())
                            <button class="btn btn-sm btn-link text-primary p-0 ms-2 flex-shrink-0"
                                    wire:click="markAsRead({{ $notification->id }})"
                                    title="Mark as read">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    @endif
</div>