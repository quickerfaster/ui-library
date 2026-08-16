<div>
    @if(!$workflow)
        <div class="text-muted">No workflow found.</div>
    @else
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if($workflow->isPending())
                @if($canApprove)
                    <button wire:click="openCommentModal('approve')" class="btn btn-success btn-sm">
                        <i class="fas fa-check-circle me-1"></i> Approve
                    </button>
                @endif
                @if($canReject)
                    <button wire:click="openCommentModal('reject')" class="btn btn-danger btn-sm">
                        <i class="fas fa-times-circle me-1"></i> Reject
                    </button>
                @endif
                @if($canRecall)
                    <button wire:click="recall" class="btn btn-warning btn-sm"
                            wire:confirm="Are you sure you want to recall this workflow?">
                        <i class="fas fa-undo-alt me-1"></i> Recall
                    </button>
                @endif
                <span class="ms-1">
                    <x-qf::status.approval-status-badge :status="$workflow->status" />
                </span>
            @else
                <x-qf::status.approval-status-badge :status="$workflow->status" />
            @endif
        </div>

        @if($workflow->isPending() && $approvers)
            <div class="small text-muted mt-2">
                <i class="fas fa-user-clock me-1"></i>
                Awaiting:
                @foreach($approvers as $approver)
                    <span class="badge bg-light text-dark border me-1">
                        @if($approver['avatar'])
                            <img src="{{ $approver['avatar'] }}" alt="{{ $approver['label'] }}"
                                 class="rounded-circle me-1" width="16" height="16">
                        @endif
                        {{ $approver['label'] }}
                    </span>
                @endforeach
            </div>
        @endif
    @endif

    {{-- Comment Modal (Bootstrap 5, no Alpine) --}}
    @if($showCommentModal)
        <div class="modal fade show d-block" tabindex="-1"
             style="background-color: rgba(0,0,0,0.5);" wire:ignore.self>
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ ucfirst($actionType) }} Workflow</h5>
                        <button type="button" class="btn-close"
                                wire:click="$set('showCommentModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="comments" class="form-label">Comments (optional)</label>
                            <textarea wire:model="comments" class="form-control" rows="3"
                                      placeholder="Add your comments here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                wire:click="$set('showCommentModal', false)">Cancel</button>
                        <button type="button" class="btn btn-primary"
                                wire:click="confirmAction">Confirm {{ ucfirst($actionType) }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>