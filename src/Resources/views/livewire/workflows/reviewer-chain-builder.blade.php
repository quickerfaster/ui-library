<div class="qf-review-chain">
    @if (empty($value))
        <div class="alert alert-light border mb-3">
            <i class="fas fa-info-circle text-muted me-1"></i>
            No review steps yet. Reviewers are optional — add one or more ordered review steps below.
        </div>
    @else
        <div class="alert alert-info small mb-3">
            <i class="fas fa-arrow-down me-1"></i>
            Reviewers must approve <strong>in order</strong>. Within a step, alternatives are parallel
            <span class="text-muted">(“any of these” / “all must review”)</span>.
        </div>

        @foreach ($value as $index => $step)
            <div class="card border mb-3 qf-review-chain-step">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="qf-review-step-number">{{ $index + 1 }}</span>
                        <span class="fw-bold ms-2">Step {{ $index + 1 }}</span>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" title="Move up"
                            @if ($index === 0) disabled @endif
                            wire:click="moveStep({{ $index }}, 'up')">
                            <i class="fas fa-chevron-up"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" title="Move down"
                            @if ($index === count($value) - 1) disabled @endif
                            wire:click="moveStep({{ $index }}, 'down')">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" title="Remove step"
                            wire:click="removeStep({{ $index }})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label">Step label</label>
                            <input type="text" class="form-control @error('value.' . $index . '.name') is-invalid @enderror"
                                placeholder="e.g. Manager Review" wire:model="value.{{ $index }}.name" />
                            @error('value.' . $index . '.name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label d-block">Resolution</label>
                            <div class="btn-group" role="group" aria-label="Resolution mode">
                                <input type="radio" class="btn-check" id="any-{{ $index }}"
                                    wire:model="value.{{ $index }}.resolution_mode" value="any" autocomplete="off" />
                                <label class="btn btn-outline-primary btn-sm" for="any-{{ $index }}">Any one</label>

                                <input type="radio" class="btn-check" id="all-{{ $index }}"
                                    wire:model="value.{{ $index }}.resolution_mode" value="all" autocomplete="off" />
                                <label class="btn btn-outline-primary btn-sm" for="all-{{ $index }}">All must review</label>
                            </div>
                        </div>
                    </div>

                    <label class="form-label">
                        Reviewers
                        @if (($step['resolution_mode'] ?? 'any') === 'any')
                            <span class="text-muted small">(any of these)</span>
                        @else
                            <span class="text-muted small">(all must review)</span>
                        @endif
                    </label>

                    @if (!empty($step['assignees']))
                        <div class="mb-2">
                            @foreach ($step['assignees'] as $assigneeIndex => $assignee)
                                <span class="badge {{ ($assignee['type'] ?? 'user') === 'role' ? 'bg-info' : 'bg-primary' }} me-1 mb-1">
                                    <i class="fas {{ ($assignee['type'] ?? 'user') === 'role' ? 'fa-user-tag' : 'fa-user' }} me-1"></i>
                                    {{ $assignee['label'] }}
                                    <button type="button" class="btn-close btn-close-white ms-1"
                                        style="font-size: 0.5rem;"
                                        wire:click="removeAssignee({{ $index }}, {{ $assigneeIndex }})"></button>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <input type="text" class="form-control form-control-sm" placeholder="Search users or roles…"
                        wire:model.live.debounce.300ms="searches.{{ $index }}" />

                    @if (!empty($searches[$index]) && !empty($searchResults[$index]))
                        <ul class="list-group mt-1">
                            @foreach ($searchResults[$index] as $resultIndex => $result)
                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                    style="cursor: pointer;"
                                    wire:click="addFromSearch({{ $index }}, {{ $resultIndex }})">
                                    <span>
                                        <i class="fas {{ ($result['type'] ?? 'user') === 'role' ? 'fa-user-tag' : 'fa-user' }} text-muted me-2"></i>
                                        {{ $result['label'] }}
                                        <span class="badge {{ ($result['type'] ?? 'user') === 'role' ? 'bg-info' : 'bg-light text-dark' }} ms-1">
                                            {{ $result['type'] }}
                                        </span>
                                    </span>
                                    <i class="fas fa-plus text-primary"></i>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addStep">
        <i class="fas fa-plus me-1"></i> Add Review Step
    </button>
</div>