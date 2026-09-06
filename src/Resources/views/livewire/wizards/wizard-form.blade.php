<div>
    @if($errors->any())
        <div class="alert alert-danger mt-3">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @foreach($displayGroups as $groupKey => $group)
        <div class="mb-4">
            <h5>{{ $group['title'] ?? ucfirst($groupKey) }}</h5>
            <div class="row">
                @foreach($group['fields'] as $fieldName)
                    @if(!$this->isFieldHidden($fieldName, 'onNewForm'))
                        @php
                            $field = $this->getField($fieldName);
                            $fieldType = $this->fieldDefinitions[$fieldName]['field_type'] ?? 'string';
                        @endphp
                        <div class="col-md-6">
                            {!! $field->renderForm($this->fields[$fieldName] ?? null) !!}

                            {{-- Generic field hints system --}}
                            @php $hints = $this->fieldDefinitions[$fieldName]['hints'] ?? []; @endphp
                            @if(in_array('showInfo', $hints) && !empty($this->fields[$fieldName]))
                                @php $fieldInfo = $this->getFieldInfo($fieldName); @endphp
                                @if($fieldInfo)
                                    <div class="mt-2 mb-3">
                                        @if(isset($fieldInfo['description']) && $fieldInfo['description'])
                                            <p class="text-muted small mb-2">{{ $fieldInfo['description'] }}</p>
                                        @endif
                                        <div class="d-flex flex-wrap gap-1">
                                            @if(!empty($fieldInfo['requires_approval']))
                                                <span class="badge bg-warning text-dark">Requires Approval</span>
                                            @endif
                                            @if(!empty($fieldInfo['deducts_from_balance']))
                                                <span class="badge bg-info text-dark">Deducts from Balance</span>
                                            @endif
                                            @if(!empty($fieldInfo['max_days_per_request']))
                                                <span class="badge bg-secondary">Max {{ $fieldInfo['max_days_per_request'] }} days per request</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if(in_array('showDuration', $hints))
                                @php $duration = $this->getFieldDuration($fieldName); @endphp
                                @if($duration !== null)
                                    <span class="text-sm text-muted">
                                        Duration: {{ $duration }} working day{{ $duration !== 1 ? 's' : '' }}
                                    </span>
                                @endif
                            @endif

                            @if(in_array('showConflicts', $hints))
                                @php $conflicts = $this->getFieldConflicts($fieldName); @endphp
                                @if(!empty($conflicts))
                                    @foreach($conflicts as $warning)
                                        <div class="alert alert-warning mt-2 mb-0 py-2 px-3">
                                            <small>{{ $warning }}</small>
                                        </div>
                                    @endforeach
                                @endif
                            @endif

                            {{-- P1-3: Character count for textarea fields --}}
                            @if($fieldType === 'textarea')
                                @php
                                    $currentValue = $this->fields[$fieldName] ?? '';
                                    $currentLength = mb_strlen($currentValue);
                                    $maxLength = null;

                                    // Parse max length from validation rule
                                    $validation = $this->fieldDefinitions[$fieldName]['validation'] ?? '';
                                    if (preg_match('/max:(\d+)/', $validation, $matches)) {
                                        $maxLength = (int) $matches[1];
                                    }
                                @endphp
                                @if($maxLength)
                                    <small class="text-muted float-end">{{ $currentLength }}/{{ $maxLength }}</small>
                                @endif
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>