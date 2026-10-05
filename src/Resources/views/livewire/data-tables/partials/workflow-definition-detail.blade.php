<div>
    {{-- Default field groups from the data config --}}
    <div class="row g-4">
        @forelse($fieldGroups as $group)
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    @if (!empty($group['title']))
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                            <h5 class="fw-bold text-primary mb-0">{{ $group['title'] }}</h5>
                        </div>
                    @endif
                    <div class="card-body p-4">
                        <div class="row gy-3">
                            @foreach ($group['fields'] as $field)
                                @if (!in_array($field, $hiddenFields['onDetail'] ?? []))
                                    @php
                                        $definition = $fieldDefinitions[$field] ?? null;
                                        if (!$definition) continue;
                                        $fieldObj = $this->getField($field);
                                    @endphp
                                    <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                        {{ $fieldObj->getLabel() }}
                                    </div>
                                    <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                        {!! $fieldObj->renderDetail($record->$field, $record) ?? '<span class="text-muted italic">-</span>' !!}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @empty
        @endforelse

        {{-- Steps / Approvers card --}}
        @if ($record->steps && $record->steps->isNotEmpty())
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                        <h5 class="fw-bold text-primary mb-0">
                            <i class="fas fa-users me-2"></i>Approval Steps
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @foreach ($record->steps as $step)
                            @php
                                $tierIcon = match($step->tier_type) {
                                    'initiator' => 'fa-play-circle text-info',
                                    'reviewer' => 'fa-user-check text-warning',
                                    'authorizer' => 'fa-shield-alt text-success',
                                    default => 'fa-circle text-muted',
                                };
                                $assignees = is_array($step->assignees) ? $step->assignees : [];
                                $mode = $assignees['mode'] ?? 'roles';
                                $ids = $assignees['ids'] ?? [];
                                $resolutionLabel = match($step->tier_type) {
                                    'initiator' => 'Any one can submit',
                                    'authorizer' => 'Any one can approve',
                                    default => ($step->resolution_mode ?? 'any') === 'all' ? 'All must approve' : 'Any one can approve',
                                };
                            @endphp
                            <div class="d-flex align-items-start mb-3 pb-3 @if(!$loop->last) border-bottom @endif">
                                <div class="me-3 mt-1">
                                    <span class="badge rounded-pill bg-light text-dark border">
                                        {{ $loop->iteration }}
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="fas {{ $tierIcon }}"></i>
                                        <strong>{{ $step->name }}</strong>
                                        <span class="badge bg-secondary">{{ ucfirst($step->tier_type) }}</span>
                                    </div>
                                    <div class="text-muted small mb-1">{{ $resolutionLabel }}</div>
                                    @if (!empty($ids))
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach ($ids as $id)
                                                <span class="badge bg-light text-dark border">
                                                    @if ($mode === 'users' || is_int($id))
                                                        <i class="fas fa-user me-1"></i>User #{{ $id }}
                                                    @else
                                                        <i class="fas fa-tag me-1"></i>{{ ucfirst(str_replace('_', ' ', $id)) }}
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>