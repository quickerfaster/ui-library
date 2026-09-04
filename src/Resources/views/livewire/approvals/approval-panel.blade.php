<div>
    @if(!$workflow)
        <div class="text-muted">No workflow found.</div>
    @elseif($displayMode === 'banner')
        {{-- ================================================================ --}}
        {{-- BANNER MODE: Banner actions + compact step indicator + expandable --}}
        {{-- ================================================================ --}}
        @php
            $bannerClass = match($workflow->status) {
                'pending' => 'alert-warning',
                'in_progress' => 'alert-info',
                'approved', 'completed' => 'alert-success',
                'rejected' => 'alert-danger',
                'cancelled', 'recalled' => 'alert-secondary',
                default => 'alert-warning',
            };
            $bannerIcon = match($workflow->status) {
                'pending' => 'fa-clock',
                'in_progress' => 'fa-spinner',
                'approved', 'completed' => 'fa-check-circle',
                'rejected' => 'fa-times-circle',
                'cancelled', 'recalled' => 'fa-undo-alt',
                default => 'fa-clock',
            };
        @endphp

        {{-- Top: Banner --}}
        <div class="alert {{ $bannerClass }} d-flex flex-wrap align-items-center justify-content-between mb-0 py-2 px-3 rounded-bottom-0">
            <div class="d-flex align-items-center gap-2">
                <i class="fas {{ $bannerIcon }}"></i>
                <strong>{{ ucfirst($workflow->status) }} Approval</strong>
                @if($workflow->currentStep)
                    &mdash; <span>Step: {{ $workflow->currentStep->name }}</span>
                @endif
            </div>
            <div class="d-flex gap-1">
                @if($workflow->isPending())
                    @if($canReject)
                        <button wire:click="openCommentModal('reject')" class="btn btn-light btn-sm text-danger">
                            <i class="fas fa-times-circle me-1"></i> Reject
                        </button>
                    @endif
                    @if($canApprove)
                        <button wire:click="openCommentModal('approve')" class="btn btn-light btn-sm text-success">
                            <i class="fas fa-check-circle me-1"></i> Approve
                        </button>
                    @endif
                    @if($canRecall)
                        <button wire:click="openCommentModal('recall')" class="btn btn-light btn-sm text-dark">
                            <i class="fas fa-undo-alt me-1"></i> Recall
                        </button>
                    @endif
                @endif
            </div>
        </div>

        {{-- Bottom: Compact step indicator --}}
        <div class="border border-top-0 rounded-bottom px-3 py-2 bg-white">
            @if($workflow->steps && $workflow->steps->isNotEmpty())
                <div class="d-flex flex-wrap align-items-center gap-1 small">
                    <span class="text-muted me-1">Steps:</span>
                    @foreach($workflow->steps as $step)
                        @php
                            $isCurrent = $workflow->currentStep && $step->id === $workflow->currentStep->id;
                            $isCompleted = $step->isApproved();
                            $stepIcon = $isCompleted ? 'fa-check-circle text-success' : ($isCurrent ? 'fa-circle text-warning' : 'fa-circle text-muted opacity-25');
                        @endphp
                        <span class="d-inline-flex align-items-center gap-1 me-1">
                            <i class="fas {{ $stepIcon }} fa-xs"></i>
                            <span @class(['fw-semibold' => $isCurrent, 'text-muted' => !$isCurrent && !$isCompleted])>
                                {{ $step->name }}
                            </span>
                        </span>
                        @if(!$loop->last)
                            <span class="text-muted">&rarr;</span>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Expandable full history toggle --}}
            <div class="mt-2">
                <button wire:click="toggleFullHistory"
                        class="btn btn-link btn-sm text-decoration-none p-0">
                    @if($showFullHistory)
                        <i class="fas fa-chevron-up me-1"></i> Hide full history
                    @else
                        <i class="fas fa-chevron-down me-1"></i> View full history
                    @endif
                </button>
            </div>

            {{-- Expandable full timeline --}}
            @if($showFullHistory)
                <div class="mt-2">
                    @if($actions->isEmpty())
                        <div class="text-muted text-center py-3 small">No activity recorded for this workflow.</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($actions as $action)
                                <div class="list-group-item px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-semibold small">{{ $action['label'] }}</span>
                                            <span class="small text-muted ms-2">
                                                @if($action['actor'])
                                                    {{ $action['actor'] }}
                                                @else
                                                    System
                                                @endif
                                                @if($action['step_name'])
                                                    &middot; {{ $action['step_name'] }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <x-qf::status.approval-status-badge :status="$action['status']" />
                                            <span class="small text-muted">{{ $action['created_at']?->format('M d, H:i') }}</span>
                                        </div>
                                    </div>
                                    @if($action['comments'])
                                        <div class="small text-muted mt-1">
                                            <i class="fas fa-comment-dots me-1"></i> {{ $action['comments'] }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

    @elseif($displayMode === 'card')
        {{-- ================================================================ --}}
        {{-- CARD MODE: Full card with actions + full timeline --}}
        {{-- ================================================================ --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-clipboard-check text-primary"></i>
                    <strong>Approval</strong>
                    <x-qf::status.approval-status-badge :status="$workflow->status" />
                </div>
                <div class="d-flex gap-1">
                    @if($workflow->isPending())
                        @if($canReject)
                            <button wire:click="openCommentModal('reject')" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-times-circle me-1"></i> Reject
                            </button>
                        @endif
                        @if($canApprove)
                            <button wire:click="openCommentModal('approve')" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-check-circle me-1"></i> Approve
                            </button>
                        @endif
                        @if($canRecall)
                            <button wire:click="openCommentModal('recall')" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-undo-alt me-1"></i> Recall
                            </button>
                        @endif
                    @endif
                </div>
            </div>

            <div class="card-body">
                {{-- Approvers --}}
                @if($workflow->isPending() && $approvers)
                    <div class="small text-muted mb-3">
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

                {{-- Step indicator --}}
                @if($workflow->steps && $workflow->steps->isNotEmpty())
                    <div class="d-flex flex-wrap align-items-center gap-1 small mb-3">
                        <span class="text-muted me-1">Steps:</span>
                        @foreach($workflow->steps as $step)
                            @php
                                $isCurrent = $workflow->currentStep && $step->id === $workflow->currentStep->id;
                                $isCompleted = $step->isApproved();
                                $stepIcon = $isCompleted ? 'fa-check-circle text-success' : ($isCurrent ? 'fa-circle text-warning' : 'fa-circle text-muted opacity-25');
                            @endphp
                            <span class="d-inline-flex align-items-center gap-1 me-1">
                                <i class="fas {{ $stepIcon }} fa-xs"></i>
                                <span @class(['fw-semibold' => $isCurrent, 'text-muted' => !$isCurrent && !$isCompleted])>
                                    {{ $step->name }}
                                </span>
                            </span>
                            @if(!$loop->last)
                                <span class="text-muted">&rarr;</span>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Full timeline --}}
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
            </div>
        </div>

    @elseif($displayMode === 'inline')
        {{-- ================================================================ --}}
        {{-- INLINE MODE: Minimal inline actions only --}}
        {{-- ================================================================ --}}
        <div class="d-inline-flex align-items-center gap-2">
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
                    <button wire:click="openCommentModal('recall')" class="btn btn-warning btn-sm">
                        <i class="fas fa-undo-alt me-1"></i> Recall
                    </button>
                @endif
            @endif
            <x-qf::status.approval-status-badge :status="$workflow->status" />
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- Comment Modal (Bootstrap 5, no Alpine) --}}
    {{-- ================================================================ --}}
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