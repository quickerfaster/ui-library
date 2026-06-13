<div>
    @if(!$request)
        <button wire:click="submitForApproval" class="btn btn-primary">
            <i class="fas fa-paper-plane me-1"></i> Submit for Approval
        </button>
    @else
        <div class="d-flex gap-2 align-items-center">
            @if($request->status === 'pending')
                @if($canApprove)
                    <button wire:click="openCommentModal('approve')" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> Approve
                    </button>
                @endif
                @if($canReject)
                    <button wire:click="openCommentModal('reject')" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Reject
                    </button>
                @endif
                @if($canRecall)
                    <button wire:click="recall" class="btn btn-warning" wire:confirm="Are you sure you want to cancel this approval request?">
                        <i class="fas fa-undo-alt me-1"></i> Cancel Request
                    </button>
                @endif
                <span class="ms-2">
                    <x-qf::status.approval-status-badge :status="$request->status" />
                </span>
            @else
                <x-qf::status.approval-status-badge :status="$request->status" />
            @endif
        </div>
    @endif

    {{-- Comment Modal (Bootstrap 5, no Alpine) --}}
    @if($showCommentModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:ignore.self>
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ ucfirst($actionType) }} Request</h5>
                        <button type="button" class="btn-close" wire:click="$set('showCommentModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="comments" class="form-label">Comments (optional)</label>
                            <textarea wire:model="comments" class="form-control" rows="3" placeholder="Add your comments here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showCommentModal', false)">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="confirmAction">Confirm {{ ucfirst($actionType) }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>