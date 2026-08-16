<div>
    @if(!$workflow)
        <div class="text-muted text-center py-4">No workflow has been started yet.</div>
    @else
        <h6 class="mb-3">Workflow Timeline</h6>

        @if($actions->isEmpty())
            <div class="text-muted text-center py-4">
                <i class="fas fa-clock fa-2x mb-2 d-block opacity-50"></i>
                No activity recorded for this workflow.
            </div>
        @else
            <div class="list-group">
                @foreach($actions as $action)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center">
                                @if($action['actor_avatar'])
                                    <img src="{{ $action['actor_avatar'] }}"
                                         alt="{{ $action['actor'] }}"
                                         class="rounded-circle me-2"
                                         width="28" height="28">
                                @else
                                    <span class="rounded-circle me-2 d-inline-flex align-items-center justify-content-center bg-secondary text-white"
                                          style="width:28px;height:28px;">
                                        <i class="fas fa-user fa-xs"></i>
                                    </span>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $action['label'] }}</div>
                                    <div class="small text-muted">
                                        @if($action['actor'])
                                            {{ $action['actor'] }}
                                        @else
                                            System
                                        @endif
                                        @if($action['step_name'])
                                            &middot; {{ $action['step_name'] }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <x-qf::status.approval-status-badge :status="$action['status']" />
                                <div class="small text-muted mt-1">
                                    {{ $action['created_at']?->format('M d, Y H:i') }}
                                </div>
                            </div>
                        </div>
                        @if($action['comments'])
                            <div class="alert alert-light mt-2 mb-0 small">
                                <i class="fas fa-comment-dots me-1"></i> {{ $action['comments'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>