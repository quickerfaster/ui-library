<div>
    @if(!$request)
        <div class="text-muted text-center py-4">No approval request has been submitted yet.</div>
    @else
        <h6 class="mb-3">Approval Timeline</h6>
        <div class="list-group">
            @foreach($tiers as $tier)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $tier->name }}</strong>
                            @if($tier->status === 'approved')
                                <i class="fas fa-check-circle text-success ms-2"></i>
                            @elseif($tier->status === 'rejected')
                                <i class="fas fa-times-circle text-danger ms-2"></i>
                            @elseif($tier->status === 'pending')
                                <i class="fas fa-clock text-warning ms-2"></i>
                            @endif
                        </div>
                        <x-qf::status.approval-status-badge :status="$tier->status" />
                    </div>
                    @if($tier->approved_by)
                        <div class="small text-muted mt-1">
                            <i class="fas fa-user me-1"></i> {{ optional($tier->approver)->name ?? 'User ID: '.$tier->approved_by }}
                            @if($tier->approved_at)
                                on {{ $tier->approved_at->format('M d, Y H:i') }}
                            @endif
                        </div>
                    @endif
                    @if($tier->comments)
                        <div class="alert alert-light mt-2 mb-0 small">
                            <i class="fas fa-comment-dots me-1"></i> {{ $tier->comments }}
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Log entries (optional) --}}
            @if($request->logs->count())
                <div class="list-group-item bg-light">
                    <details>
                        <summary class="small text-muted">Activity Log</summary>
                        <div class="mt-2" style="font-size: 0.8rem;">
                            @foreach($request->logs as $log)
                                <div class="mb-1">
                                    <i class="fas fa-history me-1"></i>
                                    {{ $log->action }} by {{ optional($log->user)->name ?? 'System' }}
                                    on {{ $log->created_at->format('M d, Y H:i') }}
                                </div>
                            @endforeach
                        </div>
                    </details>
                </div>
            @endif
        </div>
    @endif
</div>