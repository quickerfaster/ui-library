<div>
    <div class="card">
        <div class="card-header pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="mb-0">
                        <i class="fas {{ match($status) { 'pending' => 'fa-inbox', 'approved' => 'fa-check-circle', 'rejected' => 'fa-times-circle', default => 'fa-list' } }} me-1"></i>
                        {{ match($status) { 'pending' => 'Pending Approvals', 'approved' => 'Approved', 'rejected' => 'Rejected', default => 'All Approvals' } }}
                    </h6>
                    <small class="text-muted">
                        {{ match($status) { 'pending' => 'Workflows awaiting your action.', 'approved' => 'Workflows that have been approved.', 'rejected' => 'Workflows that were rejected.', default => 'All workflow requests.' } }}
                    </small>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <input type="text"
                           wire:model.live.debounce.300ms="workflowKey"
                           class="form-control form-control-sm"
                           style="min-width: 180px;"
                           placeholder="Workflow key...">

                    <select wire:model.live="status" class="form-select form-select-sm" style="min-width: 140px;">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <select wire:model.live="perPage" class="form-select form-select-sm" style="min-width: 80px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body px-0 pt-0">
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            @if(isset($columns['workflow']))
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    {{ $columns['workflow']['label'] }}
                                </th>
                            @endif
                            @if(isset($columns['entity']))
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    {{ $columns['entity']['label'] }}
                                </th>
                            @endif
                            @if(isset($columns['current_step']))
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    {{ $columns['current_step']['label'] }}
                                </th>
                            @endif
                            @if(isset($columns['status']))
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    {{ $columns['status']['label'] }}
                                </th>
                            @endif
                            @if(isset($columns['submitted_at']))
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    {{ $columns['submitted_at']['label'] }}
                                </th>
                            @endif
                            @if(isset($columns['actions']))
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end">
                                    {{ $columns['actions']['label'] }}
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($workflows as $workflow)
                            <tr>
                                @if(isset($columns['workflow']))
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">{{ $this->workflowLabel($workflow) }}</span>
                                            <small class="text-muted">{{ $workflow->definition_key }}</small>
                                        </div>
                                    </td>
                                @endif
                                @if(isset($columns['entity']))
                                    <td>{{ $this->entityReference($workflow) }}</td>
                                @endif
                                @if(isset($columns['current_step']))
                                    <td>{{ $workflow->currentStep?->name ?? '—' }}</td>
                                @endif
                                @if(isset($columns['status']))
                                    <td>
                                        <x-qf::status.approval-status-badge :status="$workflow->status" />
                                    </td>
                                @endif
                                @if(isset($columns['submitted_at']))
                                    <td class="text-nowrap">
                                        {{ $workflow->submitted_at?->format('M d, Y H:i') ?? '—' }}
                                    </td>
                                @endif
                                @if(isset($columns['actions']))
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                wire:click="selectWorkflow({{ $workflow->id }})">
                                            <i class="fas fa-eye me-1"></i> View / Act
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                    No workflow requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($workflows->hasPages())
            <div class="card-footer py-3">
                {{ $workflows->links() }}
            </div>
        @endif
    </div>
</div>