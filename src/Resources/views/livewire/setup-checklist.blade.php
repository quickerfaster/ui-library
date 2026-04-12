<div>



    <!-- Progress bar -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5>Setup Progress</h5>
            <span class="badge bg-primary">{{ $completedCount }}/{{ count($items) }} completed</span>
        </div>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar" role="progressbar"
                 style="width: {{ ($completedCount / max(count($items), 1)) * 100 }}%"
                 aria-valuenow="{{ $completedCount }}" aria-valuemin="0" aria-valuemax="{{ count($items) }}">
            </div>
        </div>
    </div>

    <!-- Checklist -->
    <div class="list-group">
        @foreach($items as $index => $item)
        
            <button type="button"
                    class="list-group-item list-group-item-action d-flex align-items-center"
                    wire:click="handleItemClick({{ $index }})">
                <div class="me-3">
                    @if($status[$index] ?? false)
                        <i class="fas fa-check-circle text-success fa-lg"></i>
                    @else
                        <i class="fas fa-circle-notch text-muted fa-lg"></i>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center">
                        @if(!empty($item['icon']))
                            <i class="{{ $item['icon'] }} me-2"></i>
                        @endif
                        <strong>{{ $item['label'] }}</strong>
                    </div>
                    @if(!empty($item['description']))
                        <small class="text-muted">{{ $item['description'] }}</small>
                    @endif
                </div>
                <div>
                    @if($status[$index] ?? false)
                        <span class="badge bg-success me-2">Completed</span>
                        <i class="fas fa-chevron-right"></i>
                    @else
                        <span class="badge bg-warning text-dark me-2">Pending</span>
                        <i class="fas fa-plus-circle text-primary"></i>
                    @endif
                </div>
            </button>
        @endforeach
    </div>

    <!-- Optional: Mark setup as complete when all items are done -->
    @if($completedCount === count($items) && count($items) > 0)
        <div class="alert alert-success mt-4">
            <h5 class="alert-heading">All set!</h5>
            <p>You have completed all required setup steps. You can now proceed to the dashboard.</p>
            <hr>
            <a href="{{ url('/dashboard') }}" class="btn btn-success">Go to Dashboard</a>
        </div>
    @endif
</div>