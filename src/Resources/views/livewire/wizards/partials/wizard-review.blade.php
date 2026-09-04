@php
    use QuickerFaster\UILibrary\Services\Config\Wizards\WizardConfigResolver;
    use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
    use QuickerFaster\UILibrary\Services\Workflow\WorkflowEngine;
    $resolver = new WizardConfigResolver($configKey);
    $linkFields = $resolver->getLinkFields();
    $preview = $currentStepConfig['preview'] ?? [];
    $showBalance = $preview['showBalance'] ?? false;
    $showApprovalPath = $preview['showApprovalPath'] ?? false;
    $showTeamCalendar = $preview['showTeamCalendar'] ?? false;
@endphp

<div class="review-container">
    <h3 class="mb-4">Review Your Information</h3>
    <p class="text-muted mb-4">Please review all details before final submission.</p>

    @foreach($steps as $index => $step)
        @if(isset($step['model']) && isset($stepData[$index]))
            @php
                $modelClass = $step['model'];
                $record = $modelClass::find($stepData[$index]);
                if (!$record) continue;

                // Get model config key using the same method as Wizard
                $modelConfigKey = (new \QuickerFaster\UILibrary\Http\Livewire\Wizards\Wizard($configKey))
                    ->getModelConfigKey($modelClass);
                $configResolver = app(ConfigResolver::class, ['configKey' => $modelConfigKey]);
                $fieldDefinitions = $configResolver->getFieldDefinitions();
                $fieldGroups = $configResolver->getFieldGroups();
            @endphp

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ $step['title'] }}</h5>
                </div>
                <div class="card-body">
                    @if(!empty($step['groups']))
                        @foreach($step['groups'] as $groupKey)
                            @if(isset($fieldGroups[$groupKey]))
                                <h6 class="text-primary mt-3 mb-2">{{ $fieldGroups[$groupKey]['title'] ?? $groupKey }}</h6>
                                <dl class="row">
                                    @foreach($fieldGroups[$groupKey]['fields'] as $fieldName)
                                        @if(isset($fieldDefinitions[$fieldName]))
                                            @php
                                                $def = $fieldDefinitions[$fieldName];
                                                $value = $record->$fieldName ?? null;

                                                // Handle relationships
                                                if (isset($def['relationship'])) {
                                                    $rel = $def['relationship'];
                                                    $dynamicProp = $rel['dynamic_property'] ?? $fieldName;
                                                    //if ($record->relationLoaded($dynamicProp)) {
                                                        $related = $record->$dynamicProp;
                                                        
                                                        if ($related) {
                                                            if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
                                                                $displayField = $rel['display_field'] ?? 'name';
                                                                $value = $related->pluck($displayField)->implode(', ');
                                                            } else {
                                                                $displayField = $rel['display_field'] ?? 'name';
                                                                $value = $related->$displayField ?? '';
                                                            }
                                                        } else {
                                                            $value = '';
                                                        }
                                                   // }
                                                } elseif (isset($def['options']) && is_array($def['options'])) {
                                                    $value = $def['options'][$value] ?? $value;
                                                }
                                            @endphp
                                            <dt class="col-sm-4">{{ $def['label'] ?? $fieldName }}</dt>
                                            <dd class="col-sm-8">{{ $value ?: '—' }}</dd>
                                        @endif
                                    @endforeach
                                </dl>
                            @endif
                        @endforeach
                    @else
                        <p class="text-muted">No data available for this step.</p>
                    @endif
                </div>
            </div>
        @endif
    @endforeach

    {{-- Preview sections: Balance, Approval Path, Team Calendar --}}
    @if($showBalance || $showApprovalPath || $showTeamCalendar)
        @php
            // Find the source step (isLinkSource) to get the saved record
            $sourceRecord = null;
            foreach ($steps as $idx => $s) {
                if (isset($s['isLinkSource']) && $s['isLinkSource'] && isset($stepData[$idx])) {
                    $sourceModelClass = $s['model'];
                    $sourceRecord = $sourceModelClass::find($stepData[$idx]);
                    break;
                }
            }
        @endphp

        @if($showBalance && $sourceRecord)
            @php
                $balanceCallback = $preview['balanceCallback'] ?? null;
                $employeeId = $sourceRecord->employee_id ?? null;
                $leaveTypeId = $sourceRecord->leave_type_id ?? null;
                $startDate = $sourceRecord->start_date;
                $endDate = $sourceRecord->end_date;
                $balanceRecord = null;
                $availableBalance = null;
                $workingDays = 0;
                $remainingAfter = null;

                if ($employeeId && $leaveTypeId && $startDate && $balanceCallback) {
                    $year = $startDate instanceof \Carbon\Carbon ? $startDate->year : date('Y', strtotime($startDate));

                    $balanceRecord = app()->call($balanceCallback, [
                        'employeeId' => $employeeId,
                        'leaveTypeId' => $leaveTypeId,
                        'year' => $year,
                    ]);

                    if ($balanceRecord) {
                        $availableBalance = (float) ($balanceRecord['balance'] ?? 0);

                        // Calculate working days
                        $s = \Carbon\Carbon::parse($startDate);
                        $e = \Carbon\Carbon::parse($endDate);
                        while ($s->lte($e)) {
                            if (!$s->isWeekend()) {
                                $workingDays++;
                            }
                            $s->addDay();
                        }

                        $remainingAfter = $availableBalance - $workingDays;
                    }
                }
            @endphp

            @if($balanceCallback)
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Leave Balance</h5>
                </div>
                <div class="card-body">
                    @if($balanceRecord)
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="mb-2">
                                    <span class="text-muted">Available Balance:</span>
                                    <strong class="ms-2">{{ $availableBalance }} days</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-2">
                                    <span class="text-muted">Requested:</span>
                                    <strong class="ms-2">{{ $workingDays }} days</strong>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-0">
                            <span class="text-muted">After this request:</span>
                            <strong class="ms-2 {{ $remainingAfter < 0 ? 'text-danger' : 'text-success' }}">
                                {{ $remainingAfter }} days remaining
                            </strong>
                        </div>
                        @if($remainingAfter < 0)
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Your balance will go negative by {{ abs($remainingAfter) }} days. This request may require additional approval.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            No leave balance record found for this leave type.
                        </div>
                    @endif
                </div>
            </div>
            @endif
        @endif

        @if($showApprovalPath)
            @php
                $workflowKey = $preview['workflowKey'] ?? 'leave_request';
                $approvalSteps = [];
                try {
                    $engine = app(WorkflowEngine::class);
                    $definition = $engine->getDefinition($workflowKey);
                    if ($definition && isset($definition['steps'])) {
                        $approvalSteps = $definition['steps'];
                    }
                } catch (\Throwable $e) {
                    // Silently fall back to empty steps
                }
            @endphp

            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i>Approval Path</h5>
                </div>
                <div class="card-body">
                    @if(!empty($approvalSteps))
                        <ol class="list-group list-group-numbered">
                            @foreach($approvalSteps as $approvalStep)
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">{{ $approvalStep['name'] }}</div>
                                        @if(!empty($approvalStep['roles']))
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i>
                                                {{ implode(', ', array_map('ucfirst', $approvalStep['roles'])) }}
                                            </small>
                                        @endif
                                    </div>
                                    <span class="badge bg-secondary rounded-pill">
                                        {{ $approvalStep['approval_mode'] ?? 'any' }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-muted mb-0">No approval workflow configured for leave requests.</p>
                    @endif
                </div>
            </div>
        @endif

        @if($showTeamCalendar)
            <div class="card mb-4 border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Team Calendar</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Team calendar integration is not yet configured.</p>
                </div>
            </div>
        @endif
    @endif
</div>