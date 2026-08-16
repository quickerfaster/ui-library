<div class="qf-wizard-page py-4">
    @if ($showCompletion)
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success fa-4x"></i>
                </div>
                <h3 class="fw-bold">{{ $completion['title'] ?? 'Completed!' }}</h3>
                <p class="text-muted fs-5">
                    Workflow Definition "{{ $workflowName ?: 'Untitled' }}" (<code>{{ $workflowKey ?: '—' }}</code>) saved.
                </p>
                <div class="mt-4">
                    <a href="{{ $returnPath }}" class="btn btn-primary px-5">Back to Workflows</a>
                </div>
            </div>
        </div>
    @else
        {{-- Header --}}
        <div class="text-center mb-4">
            <h5 class="text-primary fw-bold mb-1" style="letter-spacing: 0.5px;">{{ $title }}</h5>
            <p class="text-muted small mb-0">{{ $description }}</p>
        </div>

        {{-- Step tracker --}}
        <div class="qf-wizard-steps mb-4">
            @foreach ($steps as $index => $step)
                @php
                    $state = $index === $stepIndex ? 'active' : ($index < $stepIndex ? 'completed' : '');
                @endphp
                <div class="qf-wizard-step {{ $state }}" @if ($state === 'active') aria-current="step" @endif>
                    @if ($index > 0)
                        <div class="qf-wizard-step-connector"></div>
                    @endif
                    <div class="qf-wizard-step-dot">
                        @if ($index < $stepIndex)
                            <i class="fas fa-check"></i>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </div>
                    <div class="qf-wizard-step-label">{{ $step['title'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Step 0: Details --}}
                @if ($stepIndex === 0)
                    <h4 class="fw-bold mb-4">Workflow Details</h4>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control @error('workflowName') is-invalid @enderror"
                                wire:model.live.debounce.500ms="workflowName" placeholder="e.g. Purchase Order Approval" />
                            @error('workflowName')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Key</label>
                            <input type="text" class="form-control @error('workflowKey') is-invalid @enderror"
                                wire:model="workflowKey" placeholder="purchase_order" />
                            @error('workflowKey')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Unique machine name (a-z, 0-9, _).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Entity Type</label>
                            <input type="text" class="form-control @error('entityType') is-invalid @enderror"
                                wire:model="entityType" placeholder="e.g. Purchase Order" />
                            @error('entityType')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="isActive" wire:model="isActive" />
                                <label class="form-check-label" for="isActive">
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" wire:model="workflowDescription"
                                placeholder="Optional description shown in the workflow listing."></textarea>
                        </div>
                    </div>
                @endif

                {{-- Step 1: Initiators --}}
                @if ($stepIndex === 1)
                    <h4 class="fw-bold mb-1">Add Initiators</h4>
                    <p class="text-muted mb-4">Anyone listed here can initiate this workflow. Resolution: any one can submit.</p>

                    <label class="form-label d-block">Assignment Mode</label>
                    <div class="btn-group mb-3" role="group" aria-label="Initiator assignment mode">
                        <input type="radio" class="btn-check" id="initiator-users" wire:model.live="initiatorMode" value="users" autocomplete="off" />
                        <label class="btn btn-outline-primary btn-sm" for="initiator-users">Specific Users</label>
                        <input type="radio" class="btn-check" id="initiator-roles" wire:model.live="initiatorMode" value="roles" autocomplete="off" />
                        <label class="btn btn-outline-primary btn-sm" for="initiator-roles">By Role</label>
                        <input type="radio" class="btn-check" id="initiator-mixed" wire:model.live="initiatorMode" value="mixed" autocomplete="off" />
                        <label class="btn btn-outline-primary btn-sm" for="initiator-mixed">Mixed</label>
                    </div>

                    <div class="row">
                        @if (in_array($initiatorMode, ['users', 'mixed']))
                            <div class="col-md-6">
                                {!! $this->getField('initiator_users')->renderForm($fields['initiator_users'] ?? []) !!}
                            </div>
                        @endif
                        @if (in_array($initiatorMode, ['roles', 'mixed']))
                            <div class="col-md-6">
                                {!! $this->getField('initiator_roles')->renderForm($fields['initiator_roles'] ?? []) !!}
                            </div>
                        @endif
                    </div>

                    @error('initiatorAssignees')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                @endif

                {{-- Step 2: Reviewers --}}
                @if ($stepIndex === 2)
                    <h4 class="fw-bold mb-4">Add Reviewers</h4>
                    <livewire:qf.reviewer-chain-builder wire:model="reviewSteps" />
                @endif

                {{-- Step 3: Authorizers --}}
                @if ($stepIndex === 3)
                    <h4 class="fw-bold mb-1">Add Authorizers</h4>
                    <p class="text-muted mb-4">Any one of these authorizers can give final approval. Resolution: any one can approve.</p>

                    <label class="form-label d-block">Assignment Mode</label>
                    <div class="btn-group mb-3" role="group" aria-label="Authorizer assignment mode">
                        <input type="radio" class="btn-check" id="authorizer-users" wire:model.live="authorizerMode" value="users" autocomplete="off" />
                        <label class="btn btn-outline-primary btn-sm" for="authorizer-users">Specific Users</label>
                        <input type="radio" class="btn-check" id="authorizer-roles" wire:model.live="authorizerMode" value="roles" autocomplete="off" />
                        <label class="btn btn-outline-primary btn-sm" for="authorizer-roles">By Role</label>
                        <input type="radio" class="btn-check" id="authorizer-mixed" wire:model.live="authorizerMode" value="mixed" autocomplete="off" />
                        <label class="btn btn-outline-primary btn-sm" for="authorizer-mixed">Mixed</label>
                    </div>

                    <div class="row">
                        @if (in_array($authorizerMode, ['users', 'mixed']))
                            <div class="col-md-6">
                                {!! $this->getField('authorizer_users')->renderForm($fields['authorizer_users'] ?? []) !!}
                            </div>
                        @endif
                        @if (in_array($authorizerMode, ['roles', 'mixed']))
                            <div class="col-md-6">
                                {!! $this->getField('authorizer_roles')->renderForm($fields['authorizer_roles'] ?? []) !!}
                            </div>
                        @endif
                    </div>

                    @error('authorizerAssignees')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                @endif

                {{-- Step 4: Summary --}}
                @if ($stepIndex === 4)
                    <h5 class="fw-bold mb-4">Review & save "{{ $workflowName ?: 'Untitled' }}"</h5>

                    <div class="row g-4">
                        {{-- Primary: merged Approval Flow timeline --}}
                        <div class="col-lg-7">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-transparent fw-bold">
                                    <i class="fas fa-diagram-project me-2 text-primary"></i>Approval Flow
                                </div>
                                <div class="card-body">
                                    @php $nodes = $this->pipelineNodes(); @endphp
                                    <ul class="qf-summary-timeline">
                                        @forelse ($nodes as $i => $node)
                                            <li class="qf-summary-timeline-item">
                                                <span class="qf-summary-timeline-marker">{{ $i + 1 }}</span>
                                                @if ($i < count($nodes) - 1)
                                                    <span class="qf-summary-timeline-connector"></span>
                                                @endif
                                                <div class="qf-summary-timeline-content">
                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                        <span class="fw-bold">{{ $node['label'] }}</span>
                                                        <span class="qf-resolution-badge">{{ $node['resolution'] }}</span>
                                                    </div>
                                                    <div class="mt-1">
                                                        @forelse ($node['assignees'] as $a)
                                                            <span class="badge {{ ($a['type'] ?? 'user') === 'role' ? 'bg-info' : 'bg-primary' }} me-1">{{ $a['label'] }}</span>
                                                        @empty
                                                            <span class="text-muted small">None</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </li>
                                        @empty
                                            <li class="text-muted">No approval flow configured.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Secondary: Details + Notifications --}}
                        <div class="col-lg-5">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-header bg-transparent fw-bold">
                                    <i class="fas fa-info-circle me-2 text-primary"></i>Workflow Details
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-3">Name</dt>
                                        <dd class="col-sm-9">{{ $workflowName ?: '—' }}</dd>
                                        <dt class="col-sm-3">Key</dt>
                                        <dd class="col-sm-9"><code>{{ $workflowKey ?: '—' }}</code></dd>
                                        <dt class="col-sm-3">Entity</dt>
                                        <dd class="col-sm-9">{{ $entityType ?: '—' }}</dd>
                                        <dt class="col-sm-3">Status</dt>
                                        <dd class="col-sm-9">
                                            <span class="badge {{ $isActive ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $isActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </dd>
                                    </dl>
                                </div>
                            </div>

                            {{-- Notifications configuration --}}
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-transparent fw-bold">
                                    <i class="fas fa-bell me-2 text-primary"></i>Notifications
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3"> Choose which workflow transitions trigger notifications. Notifications are enabled when at least one toggle is on.</p>

                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify-submitted" wire:model="notifyOnSubmitted">
                                        <label class="form-check-label" for="notify-submitted">Notify on submit</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify-approved" wire:model="notifyOnApproved">
                                        <label class="form-check-label" for="notify-approved">Notify on approve</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify-rejected" wire:model="notifyOnRejected">
                                        <label class="form-check-label" for="notify-rejected">Notify on reject</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify-recalled" wire:model="notifyOnRecalled">
                                        <label class="form-check-label" for="notify-recalled">Notify on recall</label>
                                    </div>
                                    <div class="form-text">Template names use sensible defaults (workflow_submitted, workflow_approved, etc.).</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Navigation --}}
        <div class="d-flex justify-content-between align-items-center mt-4">
            <button type="button" class="btn btn-link text-decoration-none text-muted fw-bold p-0"
                @if ($stepIndex > 0) wire:click="previous" @else disabled style="opacity:0" @endif>
                <i class="fas fa-chevron-left me-1"></i> Back
            </button>

            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-link text-decoration-none text-danger me-4 fw-bold p-0"
                    wire:click="cancel">
                    Cancel
                </button>

                @if ($stepIndex === 4)
                    <button type="button" class="btn btn-success btn-lg px-5 shadow-sm fw-bold" wire:click="finish">
                        Save Workflow
                    </button>
                @else
                    <button type="button" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold" wire:click="next">
                        Continue <i class="fas fa-chevron-right ms-2"></i>
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>