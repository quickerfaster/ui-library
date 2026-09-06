@php
    use QuickerFaster\UILibrary\Services\Notifications\NotificationTypeRegistry;
@endphp

<div>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Notification Preferences</h5>
            <p class="text-muted text-sm mb-0">Choose how you receive each notification type. Uncheck a channel to stop receiving that notification type through it.</p>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Notification Type</th>
                        @foreach ($channels as $channel)
                            <th class="text-center text-uppercase text-xs">{{ $channel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($types as $type)
                        <tr>
                            <td class="ps-3">
                                <i class="{{ NotificationTypeRegistry::getIcon($type) }} {{ NotificationTypeRegistry::getColor($type) }} me-2"></i>
                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $type)) }}
                            </td>
                            @foreach ($channels as $channel)
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input"
                                        wire:click="toggle('{{ $type }}', '{{ $channel }}')"
                                        @checked($this->isEnabled($type, $channel))>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($channels) + 1 }}" class="text-center text-muted py-4">
                                No notification templates defined yet. Create templates to manage preferences.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>