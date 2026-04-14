<div>
    <div class="detail-page-wrapper mb-5 p-2">
        {{-- Header Section --}}
        @php
            $module = strtolower($moduleName);
            $displayModelName = ucwords(str_replace(['_', '-'], ' ', \Str::snake($modelName)));
            $modelPlural = \Str::plural(\Str::kebab($modelName));
            $backUrl = url("/{$module}/{$modelPlural}");
        @endphp

        <div class="d-flex justify-content-between align-items-center my-4 d-print-none">
            <div class="d-flex flex-column">
                {{-- Back Link --}}
                <a wire:navigate href="{{ $backUrl }}" class="text-decoration-none text-muted small fw-bold mb-2 d-inline-flex align-items-center hover-primary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Employees
                </a>
                <div class="d-flex align-items-center">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" class="rounded-circle me-3" width="60" height="60" alt="Photo">
                    @else
                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:60px;height:60px;">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                    @endif
                    <div>
                        <h2 class="fw-bold text-dark mb-0">{{ $fullName }}</h2>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <span class="text-muted small"><i class="fas fa-briefcase me-1"></i> {{ $jobTitle }}</span>
                            <span class="text-muted small"><i class="fas fa-building me-1"></i> {{ $departmentName }}</span>
                            <span class="badge bg-{{ $status == 'Active' ? 'success' : 'secondary' }}">{{ $status }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                @if ($recordIds)
                    <button wire:click="previous" class="btn btn-outline-secondary shadow-sm px-3" {{ $currentIndex == 0 ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-left me-1"></i> Previous
                    </button>
                    <button wire:click="next" class="btn btn-outline-secondary shadow-sm px-3" {{ $currentIndex == count($recordIds) - 1 ? 'disabled' : '' }}>
                        Next <i class="fas fa-chevron-right ms-1"></i>
                    </button>
                @endif
                <button type="button" onclick="window.print();" class="btn btn-outline-secondary shadow-sm px-3">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                @if ($inline)
                    @php
                        $editUrl = url('/' . Str::plural(Str::kebab($modelName)) . "/{$recordId}/edit");
                        if (!empty($returnParams)) $editUrl .= '?' . http_build_query($returnParams);
                    @endphp
                    <a wire:navigate href="{{ $editUrl }}" class="btn btn-primary bg-gradient-primary shadow-sm px-4">
                        <i class="fas fa-edit me-1"></i> Edit Employee
                    </a>
                @else
                    <button onclick="Livewire.dispatch('openEditModal', ['{{ $configKey }}', {{ $recordId }}])" class="btn btn-primary bg-gradient-primary shadow-sm px-4">
                        <i class="fas fa-edit me-1"></i> Edit Employee
                    </button>
                @endif
            </div>
        </div>

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item"><button wire:navigate wire:click="$set('activeTab', 'overview')" class="nav-link {{ $activeTab == 'overview' ? 'active' : '' }}">Overview</button></li>
            <li class="nav-item"><button wire:navigate wire:click="$set('activeTab', 'personal')" class="nav-link {{ $activeTab == 'personal' ? 'active' : '' }}">Personal</button></li>
            <li class="nav-item"><button wire:navigate wire:click="$set('activeTab', 'employment')" class="nav-link {{ $activeTab == 'employment' ? 'active' : '' }}">Employment</button></li>
            <li class="nav-item"><button wire:navigate wire:click="$set('activeTab', 'payroll')" class="nav-link {{ $activeTab == 'payroll' ? 'active' : '' }}">Payroll</button></li>
            <li class="nav-item"><button wire:navigate wire:click="$set('activeTab', 'workpatterns')" class="nav-link {{ $activeTab == 'workpatterns' ? 'active' : '' }}">Work Patterns</button></li>
            <li class="nav-item"><button wire:navigate wire:click="$set('activeTab', 'attendance')" class="nav-link {{ $activeTab == 'attendance' ? 'active' : '' }}">Attendance</button></li>
            <li class="nav-item"><button wire:navigate wire:click="$set('activeTab', 'timeoff')" class="nav-link {{ $activeTab == 'timeoff' ? 'active' : '' }}">Time Off</button></li>
            <li class="nav-item"><button wire:navigate wire:click="$set('activeTab', 'documents')" class="nav-link {{ $activeTab == 'documents' ? 'active' : '' }}">Documents</button></li>
            <li class="nav-item"><button wire:navigate wire:click="$set('activeTab', 'clockevents')" class="nav-link {{ $activeTab == 'clockevents' ? 'active' : '' }}">Clock Events</button></li>
        </ul>

        <div class="tab-content">


@if ($activeTab == 'overview')
    @livewire('qf.dashboard', [
        'configKey' => 'hr.dashboards.dashboard_employee_overview',
        'parameters' => [
            'employee_number' => $employee->employee_number,
            'employee_email' => $employee->email,
            'employee_phone' => $profile->personal_phone ?? $employee->phone ?? '',
            'tenure_years' => now()->diffInYears($employee->hire_date),
            'days_until_anniversary' => $this->getDaysUntilAnniversary(),
        ]
    ])
@endif







            {{-- Personal Tab --}}
            @if ($activeTab == 'personal')
                <div class="row g-4">
                    {{-- Personal Information Card --}}
                    <div class="col-12 col-xl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-primary mb-0">Personal Information</h5>
                                <button onclick="Livewire.dispatch('openEditModal', ['{{ $configKey }}', {{ $recordId }}])" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                            <div class="card-body p-4">
                                <div class="row gy-3">
                                    @foreach (['first_name', 'last_name', 'date_of_birth', 'gender', 'marital_status', 'nationality'] as $field)
                                        <div class="col-sm-4 text-muted fw-semibold small text-uppercase">{{ $fieldDefinitions[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}</div>
                                        <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">{!! $this->renderField('employee', $field, $employee->$field) ?? '<span class="text-muted italic">-</span>' !!}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Contact Information Card (EmployeeProfile) --}}
                    <div class="col-12 col-xl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-primary mb-0">Contact Information</h5>
                                @if($profile)
                                    <button onclick="Livewire.dispatch('openEditModal', ['hr.employee_profile', {{ $profile->id }}])" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                @else
                                    <button onclick="Livewire.dispatch('openAddModal', ['hr.employee_profile', { employee_id: '{{ $employee->employee_number }}' }])" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                @endif
                            </div>
                            <div class="card-body p-4">
                                <div class="row gy-3">
                                    @foreach (['personal_email', 'personal_phone', 'work_phone', 'address_street', 'address_city', 'address_state', 'address_postal_code', 'address_country'] as $field)
                                        <div class="col-sm-4 text-muted fw-semibold small text-uppercase">{{ $profileFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}</div>
                                        <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">{!! $this->renderField('profile', $field, $profile->$field ?? null) ?? '<span class="text-muted italic">-</span>' !!}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Emergency Contact Card --}}
                    <div class="col-12 col-xl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                                <h5 class="fw-bold text-primary mb-0">Emergency Contact</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row gy-3">
                                    @foreach (['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'] as $field)
                                        <div class="col-sm-4 text-muted fw-semibold small text-uppercase">{{ $profileFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}</div>
                                        <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">{!! $this->renderField('profile', $field, $profile->$field ?? null) ?? '<span class="text-muted italic">-</span>' !!}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Identification Documents Card --}}
                    <div class="col-12 col-xl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                                <h5 class="fw-bold text-primary mb-0">Identification</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row gy-3">
                                    @foreach (['passport_number', 'passport_expiry_date', 'national_id_number'] as $field)
                                        <div class="col-sm-4 text-muted fw-semibold small text-uppercase">{{ $profileFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}</div>
                                        <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">{!! $this->renderField('profile', $field, $profile->$field ?? null) ?? '<span class="text-muted italic">-</span>' !!}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Employment Tab --}}
            @if ($activeTab == 'employment')
                <div class="row g-4">
                    <div class="col-12 col-xl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-primary mb-0">Job Details</h5>
                                @if($position)
                                    <button onclick="Livewire.dispatch('openEditModal', ['hr.employee_position', {{ $position->id }}])" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                @else
                                    <button onclick="Livewire.dispatch('openAddModal', ['hr.employee_position', { employee_id: '{{ $employee->employee_number }}' }])" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                @endif
                            </div>
                            <div class="card-body p-4">
                                <div class="row gy-3">
                                    @foreach (['job_title_id', 'department_id', 'manager_id', 'reports_to', 'location_id', 'shift_id', 'attendance_policy_id'] as $field)
                                        <div class="col-sm-4 text-muted fw-semibold small text-uppercase">{{ $positionFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}</div>
                                        <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">{!! $this->renderField('position', $field, $position->$field ?? null) ?? '<span class="text-muted italic">-</span>' !!}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                                <h5 class="fw-bold text-primary mb-0">Compensation</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row gy-3">
                                    @foreach (['pay_type', 'hourly_rate', 'base_salary', 'salary_currency', 'pay_frequency'] as $field)
                                        <div class="col-sm-4 text-muted fw-semibold small text-uppercase">{{ $positionFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}</div>
                                        <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">{!! $this->renderField('position', $field, $position->$field ?? null) ?? '<span class="text-muted italic">-</span>' !!}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Payroll Tab --}}
            @if ($activeTab == 'payroll')
                <div class="row g-4">
                    <div class="col-12 col-xl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-primary mb-0">Bank Information</h5>
                                @if($payrollProfile)
                                    <button onclick="Livewire.dispatch('openEditModal', ['hr.employee_payroll_profile', {{ $payrollProfile->id }}])" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                @else
                                    <button onclick="Livewire.dispatch('openAddModal', 'hr.employee_payroll_profile')" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                @endif
                            </div>
                            <div class="card-body p-4">
                                <div class="row gy-3">
                                    @foreach (['bank_account', 'bank_routing'] as $field)
                                        <div class="col-sm-4 text-muted fw-semibold small text-uppercase">{{ $payrollFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}</div>
                                        <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">{!! $this->renderField('payroll', $field, $payrollProfile->$field ?? null) ?? '<span class="text-muted italic">-</span>' !!}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                                <h5 class="fw-bold text-primary mb-0">Tax Withholding</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row gy-3">
                                    @foreach (['tax_filing_status', 'allowances', 'is_exempt_from_federal_tax'] as $field)
                                        <div class="col-sm-4 text-muted fw-semibold small text-uppercase">{{ $payrollFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}</div>
                                        <div class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">{!! $this->renderField('payroll', $field, $payrollProfile->$field ?? null) ?? '<span class="text-muted italic">-</span>' !!}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Work Patterns Tab --}}
            @if ($activeTab == 'workpatterns')
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-primary mb-0">Work Patterns</h5>
                        <button onclick="Livewire.dispatch('openAddModal', 'hr.employee_work_pattern')" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus"></i> Add Pattern
                        </button>
                    </div>
                    <div class="card-body p-4">
                        @if ($workPatterns && $workPatterns->count())
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr><th>Work Pattern</th><th>Start Date</th><th>End Date</th><th style="width: 80px"></th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($workPatterns as $pattern)
                                            <tr>
                                                <td>{{ $pattern->workPattern?->name ?? '' }}</td>
                                                <td>{{ $pattern->start_date->format('M d, Y') }}</td>
                                                <td>{{ $pattern->end_date ? $pattern->end_date->format('M d, Y') : 'Ongoing' }}</td>
                                                <td>
                                                    <button onclick="Livewire.dispatch('openEditModal', ['hr.employee_work_pattern', {{ $pattern->id }}])" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">No work patterns assigned.</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Attendance Tab --}}
            @if ($activeTab === 'attendance')
                @livewire('qf.data-table', [
                    'configKey' => 'hr.attendance',
                    'queryFilters' => [['employee_number', '=', $employee->employee_number]],
                    'hiddenFields' => ['onTable' => ['employee_id', 'employee_number']],
                ], key('attendance-' . $recordId))
            @endif

            {{-- Time Off Tab --}}
            @if ($activeTab === 'timeoff')
                @livewire('qf.data-table', [
                    'configKey' => 'hr.leave_request',
                    'queryFilters' => [['employee_id', '=', $employee->employee_number]],
                    'hiddenFields' => ['onTable' => ['employee_id']],
                ], key('timeoff-' . $recordId))
            @endif

            {{-- Documents Tab --}}
            @if ($activeTab === 'documents')
                <div class="mb-3 d-flex justify-content-end">
                    <button onclick="Livewire.dispatch('openAddModal', ['hr.document', { name: 'test name',   employee_id: '{{ $employee->id }}' }])" class="btn btn-sm btn-primary">
                        <i class="fas fa-upload"></i> Upload Document
                    </button>
                </div>
                @livewire('qf.data-table', [
                    'configKey' => 'hr.document',
                    'queryFilters' => [['employee_number', '=', $employee->employee_number]],
                    'hiddenFields' => ['onTable' => ['employee_number']],
                ], key('documents-' . $recordId))
            @endif

            {{-- Clock Events Tab --}}
            @if ($activeTab == 'clockevents')
                @livewire('qf.data-table', [
                    'configKey' => 'hr.clock_event',
                    'queryFilters' => [['employee_number', '=', $employee->employee_number]],
                    'sort' => ['field' => 'timestamp', 'direction' => 'desc'],
                    'hiddenFields' => ['onTable' => ['employee_number']],
                ], key('clockevents-' . $recordId))
            @endif
        </div>
    </div>

    <style>
        .detail-page-wrapper { font-size: 0.95rem; }
        .hover-primary:hover { color: var(--bs-primary) !important; }
        .card { border-radius: 12px; }
        .nav-tabs .nav-link { color: #6c757d; font-weight: 500; }
        .nav-tabs .nav-link.active { color: var(--bs-primary); border-bottom: 2px solid var(--bs-primary); background: transparent; }
        @media print {
            .d-print-none, .btn, nav, .sidebar, .nav-tabs { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .col-sm-4 { width: 30% !important; float: left; }
            .col-sm-8 { width: 70% !important; float: left; }
            .tab-pane { display: block !important; }
            .tab-content > .tab-pane { display: block !important; opacity: 1 !important; }
        }
    </style>
</div>