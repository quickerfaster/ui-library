 <div>
     <div class="detail-page-wrapper mb-5 p-2 d-print-none">
         {{-- Header Section (Buttons & Navigation) --}}
         @php
             $module = strtolower($moduleName);
             $displayModelName = ucwords(str_replace(['_', '-'], ' ', \Str::snake($modelName)));
             $modelPlural = \Str::plural(\Str::kebab($modelName));
             $backUrl = url("/{$module}/{$modelPlural}");
         @endphp

         {{-- Back link (only on full-page, not in modal) --}}
         @if ($inline && !$this->isSelfServiceMode)
             {{-- - inline implies that the crudType is pages not modal - --}}
             <div class="my-3">
                 <a wire:navigate href="{{ $backUrl }}"
                     class="text-decoration-none text-muted small fw-bold d-inline-flex align-items-center hover-primary">
                     <i class="fas fa-arrow-left me-2"></i> Back to {{ $displayModelName }}s
                 </a>
             </div>
         @else
             <br /> <!-- Add small top marmin -->
         @endif

         {{-- Action Buttons Row (responsive wrap) --}}
         <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
             <div class="d-flex flex-wrap gap-2 align-items-center">
                 @if ($recordIds)
                     <div style="min-width: 260px; max-width: 100%;">
                         @livewire(
                             'qf.searchable-employee-dropdown',
                             [
                                 'configKey' => $configKey,
                                 'selectedId' => $recordId,
                                 'returnParams' => $returnParams,
                             ],
                             key('employee-dropdown-' . $recordId)
                         )
                     </div>
                     <button wire:click="previous" class="btn btn-sm btn-outline-secondary shadow-sm px-3"
                         {{ $currentIndex == 0 ? 'disabled' : '' }}>
                         <i class="fas fa-chevron-left me-1"></i> Previous
                     </button>
                     <span class="text-muted">{{ ($currentIndex ?? 0) + 1 }} of {{ count($recordIds) }}</span>
                     <button wire:click="next" class="btn btn-sm btn-outline-secondary shadow-sm px-3"
                         {{ $currentIndex == count($recordIds) - 1 ? 'disabled' : '' }}>
                         Next <i class="fas fa-chevron-right ms-1"></i>
                     </button>
                 @endif
                 @if ($this->isSelfServiceMode)
                     <h2>My Profile</h2>
                 @endif
             </div>
             <div class="d-flex gap-2">
                 <a href="{{ route('hr.employees.print', $employee->id) }}" target="_blank"
                     class="btn btn-sm btn-outline-secondary shadow-sm px-3">
                     <i class="fas fa-print me-1"></i> Print
                 </a>
                 {{--  }}@if ($inline)
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
                @endif --}}
             </div>
         </div>

         {{-- Two‑Column Responsive Layout --}}
         <div class="row">


             {{-- Main Content (Tabs + Tab Content) --}}
             <div class="col-12 col-md-9">
                 {{-- Tabs --}}
                 <ul class="nav nav-tabs mb-4" role="tablist">
                     @foreach ($this->getAllowedTabs() as $tab)
                         <li class="nav-item">
                             <button wire:click="$set('activeTab', '{{ $tab }}')" wire:navigate
                                 class="nav-link {{ $activeTab == $tab ? 'active' : '' }}">
                                 {{ ucfirst($tab) }}
                             </button>
                         </li>
                     @endforeach
                 </ul>

                 <div class="tab-content">
                     {{-- Overview Tab --}}
                     @if ($activeTab == 'overview')
                         @livewire(
                             'qf.dashboard',
                             [
                                 'configKey' => 'hr.dashboards.dashboard_employee_overview',
                                 'parameters' => [
                                     'employee_number' => $employee->employee_number,
                                     'employee_email' => $employee->email,
                                     'employee_phone' => $profile->personal_phone ?? ($employee->phone ?? ''),
                                     'tenure_years' => now()->diffInYears($employee->hire_date),
                                     'days_until_anniversary' => $this->getDaysUntilAnniversary(),
                                 ],
                             ],
                             key('dashboard-' . $recordId)
                         )
                     @endif


                     {{-- Personal Tab --}}
                     @if ($activeTab == 'personal')
                         <div class="row g-4">
                             {{-- Personal Information Card --}}
                             <div class="col-12 col-xl-6">
                                 <div class="card border-0 shadow-sm h-100">
                                     <div
                                         class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                         <h5 class="fw-bold text-primary mb-0">Personal Information</h5>
                                         <button
                                             onclick="Livewire.dispatch('openEditModal', ['{{ $configKey }}', {{ $recordId }}])"
                                             class="btn btn-sm btn-outline-primary">
                                             <i class="fas fa-edit"></i> Edit
                                         </button>
                                     </div>
                                     <div class="card-body p-4">
                                         @if ($profile)
                                             <div class="row gy-3">
                                                 {{-- Core employee fields --}}
                                                 @foreach (['first_name', 'last_name'] as $field)
                                                     <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                         {{ $fieldDefinitions[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                     </div>
                                                     <div
                                                         class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                         {!! $this->renderField('employee', $field, $employee->$field) ?: '<span class="text-muted fst-italic">—</span>' !!}
                                                     </div>
                                                 @endforeach

                                                 {{-- Profile fields (fixed array syntax) --}}
                                                 @foreach (['photo', 'bio', 'preferred_name', 'date_of_birth', 'gender', 'nationality', 'marital_status'] as $field)
                                                     <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                         {{ $profileFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                     </div>
                                                     <div
                                                         class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                         {!! $this->renderField('profile', $field, $profile->$field ?? null) ?:
                                                             '<span class="text-muted fst-italic">—</span>' !!}
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-5">
                                                 <i class="fas fa-user-circle fa-3x text-muted mb-3"></i>
                                                 <p class="text-muted">No personal profile found.<br>Click "Edit" to
                                                     create one.</p>
                                             </div>
                                         @endif
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
                                         @if ($profile)
                                             <div class="row gy-3">
                                                 @foreach (['passport_number', 'passport_expiry_date', 'national_id_number'] as $field)
                                                     <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                         {{ $profileFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                     </div>
                                                     <div
                                                         class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                         {!! $this->renderField('profile', $field, $profile->$field ?? null) ?:
                                                             '<span class="text-muted fst-italic">—</span>' !!}
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <p class="text-muted">No identification data available.</p>
                                         @endif
                                     </div>
                                 </div>
                             </div>
                         </div>
                     @endif


                     {{-- Contact Tab --}}
                     @if ($activeTab == 'contact')
                         <div class="row g-4">
                             {{-- Address Information Card --}}
                             <div class="col-12 col-xl-6">
                                 <div class="card border-0 shadow-sm h-100">
                                     <div
                                         class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                         <h5 class="fw-bold text-primary mb-0">Address Information</h5>
                                         <button
                                             onclick="Livewire.dispatch('openEditModal', ['hr.employee_profile', {{ $profile?->id ?? 0 }}])"
                                             class="btn btn-sm btn-outline-primary">
                                             <i class="fas fa-edit"></i> Edit
                                         </button>
                                     </div>
                                     <div class="card-body p-4">
                                         @if ($profile)
                                             <div class="row gy-3">
                                                 @foreach (['address_street', 'address_city', 'address_state', 'address_postal_code', 'address_country'] as $field)
                                                     <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                         {{ $profileFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                     </div>
                                                     <div
                                                         class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                         {!! $this->renderField('profile', $field, $profile->$field ?? null) ?:
                                                             '<span class="text-muted fst-italic">—</span>' !!}
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <p class="text-muted">No address information available.</p>
                                         @endif
                                     </div>
                                 </div>
                             </div>

                             {{-- Contact Details Card --}}
                             <div class="col-12 col-xl-6">
                                 <div class="card border-0 shadow-sm h-100">
                                     <div
                                         class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                         <h5 class="fw-bold text-primary mb-0">Contact Details</h5>
                                         <button
                                             onclick="Livewire.dispatch('openEditModal', ['hr.employee_profile', {{ $profile?->id ?? 0 }}])"
                                             class="btn btn-sm btn-outline-primary">
                                             <i class="fas fa-edit"></i> Edit
                                         </button>
                                     </div>
                                     <div class="card-body p-4">
                                         @if ($profile)
                                             <div class="row gy-3">
                                                 @foreach (['personal_email', 'personal_phone', 'work_phone'] as $field)
                                                     <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                         {{ $profileFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                     </div>
                                                     <div
                                                         class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                         {!! $this->renderField('profile', $field, $profile->$field ?? null) ?:
                                                             '<span class="text-muted fst-italic">—</span>' !!}
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <p class="text-muted">No contact details available.</p>
                                         @endif
                                     </div>
                                 </div>
                             </div>

                             {{-- Emergency Contact Card --}}
                             <div class="col-12 col-xl-6">
                                 <div class="card border-0 shadow-sm h-100">
                                     <div
                                         class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                         <h5 class="fw-bold text-primary mb-0">Emergency Contact</h5>
                                         <button
                                             onclick="Livewire.dispatch('openEditModal', ['hr.employee_profile', {{ $profile?->id ?? 0 }}])"
                                             class="btn btn-sm btn-outline-primary">
                                             <i class="fas fa-edit"></i> Edit
                                         </button>
                                     </div>
                                     <div class="card-body p-4">
                                         @if ($profile)
                                             <div class="row gy-3">
                                                 @foreach (['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'] as $field)
                                                     <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                         {{ $profileFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                     </div>
                                                     <div
                                                         class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                         {!! $this->renderField('profile', $field, $profile->$field ?? null) ?:
                                                             '<span class="text-muted fst-italic">—</span>' !!}
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <p class="text-muted">No emergency contact available.</p>
                                         @endif
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
                                     <div
                                         class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                         <h5 class="fw-bold text-primary mb-0">Job Details</h5>
                                         @if ($currentPosition)
                                             <button
                                                 onclick="Livewire.dispatch('openEditModal', ['hr.employee_position', {{ $currentPosition->id }}])"
                                                 class="btn btn-sm btn-outline-primary">
                                                 <i class="fas fa-edit"></i> Edit
                                             </button>
                                         @else
                                             <button
                                                 onclick="Livewire.dispatch('openAddModal', ['hr.employee_position', { employee_id: {{ $employee->id }} }])"
                                                 class="btn btn-sm btn-outline-primary">
                                                 <i class="fas fa-plus"></i> Add Position
                                             </button>
                                         @endif
                                     </div>
                                     <div class="card-body p-4">
                                         @if ($currentPosition)
                                             <div class="row gy-3">
                                                 @foreach (['job_title_id', 'department_id', 'manager_id', 'reports_to', 'location_id', 'shift_id', 'attendance_policy_id'] as $field)
                                                     <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                         {{ $employeePositionFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                     </div>
                                                     <div
                                                         class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                         {!! $this->renderField('position', $field, $currentPosition->$field ?? null) ?:
                                                             '<span class="text-muted fst-italic">—</span>' !!}
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-5">
                                                 <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                                 <p class="text-muted">No job information recorded yet.<br>Click "Add
                                                     Position" to set up employment details.</p>
                                             </div>
                                         @endif
                                     </div>
                                 </div>
                             </div>

                             <div class="col-12 col-xl-6">
                                 <div class="card border-0 shadow-sm h-100">
                                     <div
                                         class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                         <h5 class="fw-bold text-primary mb-0">Compensation</h5>
                                         @if ($currentPosition)
                                             <button
                                                 onclick="Livewire.dispatch('openEditModal', ['hr.employee_position', {{ $currentPosition->id }}])"
                                                 class="btn btn-sm btn-outline-primary">
                                                 <i class="fas fa-edit"></i> Edit
                                             </button>
                                         @endif
                                     </div>
                                     <div class="card-body p-4">
                                         @if ($currentPosition)
                                             <div class="row gy-3">
                                                 @foreach (['pay_type', 'hourly_rate', 'base_salary', 'salary_currency', 'pay_frequency'] as $field)
                                                     <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                         {{ $employeePositionFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                     </div>
                                                     <div
                                                         class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                         {!! $this->renderField('position', $field, $currentPosition->$field ?? null) ?:
                                                             '<span class="text-muted fst-italic">—</span>' !!}
                                                     </div>
                                                 @endforeach
                                             </div>
                                         @else
                                             <div class="text-center py-5">
                                                 <i class="fas fa-coins fa-3x text-muted mb-3"></i>
                                                 <p class="text-muted">No compensation data available.</p>
                                             </div>
                                         @endif
                                     </div>
                                 </div>
                             </div>
                         </div>
                     @endif

                     {{-- History Tab --}}
                     @if ($activeTab == 'history')
                         <div class="card border-0 shadow-sm">
                             <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                                 <h5 class="fw-bold text-primary mb-0">Employment History</h5>
                                 <p class="text-muted small mb-0">Track record of all job changes, promotions, and
                                     salary adjustments</p>
                             </div>
                             <div class="card-body p-4">
                                 @if ($jobHistory && $jobHistory->count())
                                     <div class="table-responsive">
                                         <table class="table table-hover align-middle">
                                             <thead class="table-light">
                                                 <tr>
                                                     <th>Effective Date</th>
                                                     <th>Change Reason</th>
                                                     <th>Job Title</th>
                                                     <th>Department</th>
                                                     <th>Salary / Rate</th>
                                                     <th>Status</th>
                                                 </tr>
                                             </thead>
                                             <tbody>
                                                 @foreach ($jobHistory as $history)
                                                     <tr>
                                                         <td>
                                                             @php
                                                                 $effective =
                                                                     $history->effective_date instanceof \DateTime
                                                                         ? $history->effective_date
                                                                         : \Carbon\Carbon::parse(
                                                                             $history->effective_date,
                                                                         );
                                                             @endphp
                                                             <span
                                                                 class="fw-semibold">{{ $effective->format('M d, Y') }}</span>
                                                         </td>
                                                         <td>
                                                             <span
                                                                 class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">
                                                                 {{ $history->change_reason ?? 'Change' }}
                                                             </span>
                                                         </td>
                                                         <td>{{ $history->job_title ?? '—' }}</td>
                                                         <td>{{ $history->department ?? '—' }}</td>
                                                         <td>
                                                             @if ($history->base_salary)
                                                                 {{ number_format($history->base_salary, 2) }}
                                                                 {{ $history->salary_currency ?? '' }}
                                                             @elseif ($history->hourly_rate)
                                                                 ${{ number_format($history->hourly_rate, 2) }}/hr
                                                             @else
                                                                 —
                                                             @endif
                                                         </td>
                                                         <td>
                                                             @php
                                                                 $statusClass = match ($history->employment_status) {
                                                                     'Active' => 'success',
                                                                     'On Leave' => 'warning',
                                                                     'Terminated' => 'danger',
                                                                     'Suspended' => 'secondary',
                                                                     default => 'secondary',
                                                                 };
                                                             @endphp
                                                             <span
                                                                 class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} px-3 py-2 rounded-pill">
                                                                 {{ $history->employment_status ?? '—' }}
                                                             </span>
                                                         </td>
                                                     </tr>
                                                 @endforeach
                                             </tbody>
                                         </table>
                                     </div>
                                     <div class="mt-3 text-muted small">
                                         <i class="fas fa-info-circle"></i> Only past changes are shown here. The
                                         current position is displayed in the Employment tab.
                                     </div>
                                 @else
                                     <div class="text-center py-5">
                                         <i class="fas fa-timeline fa-3x text-muted mb-3"></i>
                                         <p class="text-muted">No employment history recorded yet.<br>Changes to job
                                             title, department, or salary will appear here automatically.</p>
                                     </div>
                                 @endif
                             </div>
                         </div>
                     @endif


                     {{-- Payroll Tab --}}
                     @if ($activeTab == 'payroll')
                         <div class="row g-4">
                             <div class="col-12 col-xl-6">
                                 <div class="card border-0 shadow-sm h-100">
                                     <div
                                         class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                         <h5 class="fw-bold text-primary mb-0">Bank Information</h5>
                                         @if ($payrollProfile)
                                             <button
                                                 onclick="Livewire.dispatch('openEditModal', ['hr.employee_payroll_profile', {{ $payrollProfile->id }}])"
                                                 class="btn btn-sm btn-outline-primary">
                                                 <i class="fas fa-edit"></i> Edit
                                             </button>
                                         @else
                                             <button
                                                 onclick="Livewire.dispatch('openAddModal', ['hr.employee_payroll_profile', { employee_id: '{{ $employee->id }}' }])"
                                                 class="btn btn-sm btn-outline-primary">
                                                 <i class="fas fa-plus"></i> Add Info
                                             </button>
                                         @endif
                                     </div>
                                     <div class="card-body p-4">
                                         <div class="row gy-3">
                                             @foreach (['bank_account', 'bank_routing'] as $field)
                                                 <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                     {{ $payrollFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                 </div>
                                                 <div
                                                     class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                     {!! $this->renderField('payroll', $field, $payrollProfile->$field ?? null) ??
                                                         '<span class="text-muted italic">-</span>' !!}</div>
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
                                                 <div class="col-sm-4 text-muted fw-semibold small text-uppercase">
                                                     {{ $payrollFieldDefs[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                 </div>
                                                 <div
                                                     class="col-sm-8 text-dark fw-medium border-bottom pb-2 border-light">
                                                     {!! $this->renderField('payroll', $field, $payrollProfile->$field ?? null) ??
                                                         '<span class="text-muted italic">-</span>' !!}</div>
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
                             <div
                                 class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                 <h5 class="fw-bold text-primary mb-0">Work Patterns</h5>
                                 <button
                                     onclick="Livewire.dispatch('openAddModal', ['hr.employee_work_pattern', { employee_id: '{{ $employee->id }}' }])"
                                     class="btn btn-sm btn-outline-primary">
                                     <i class="fas fa-plus"></i> Add
                                 </button>



                             </div>
                             <div class="card-body p-4">
                                 @if ($workPatterns && $workPatterns->count())
                                     <div class="table-responsive">
                                         <table class="table table-hover">
                                             <thead>
                                                 <tr>
                                                     <th>Work Pattern</th>
                                                     <th>Start Date</th>
                                                     <th>End Date</th>
                                                     <th style="width: 80px"></th>
                                                 </tr>
                                             </thead>
                                             <tbody>
                                                 @foreach ($workPatterns as $pattern)
                                                     <tr wire:key="work-pattern-{{ $pattern->id }}">
                                                         <td>{{ $pattern->workPattern?->name ?? '' }}</td>
                                                         <td>{{ $pattern->start_date->format('M d, Y') }}</td>
                                                         <td>{{ $pattern->end_date ? $pattern->end_date->format('M d, Y') : 'Ongoing' }}
                                                         </td>
                                                         <td>
                                                             <button
                                                                 onclick="Livewire.dispatch('openEditModal', ['hr.employee_work_pattern', {{ $pattern->id }}])"
                                                                 class="btn btn-sm btn-outline-secondary">
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
                         @livewire(
                             'qf.data-table',
                             [
                                 'configKey' => 'hr.attendance',
                                 'queryFilters' => [['employee.employee_number', '=', $employee->employee_number]],
                                 'hiddenFields' => ['onTable' => ['employee_id', 'employee_number']],
                             ],
                             key('attendance-' . $recordId)
                         )
                     @endif

                     {{-- Time Off Tab --}}
                     @if ($activeTab === 'timeoff')
                         @livewire(
                             'qf.data-table',
                             [
                                 'configKey' => 'hr.leave_request',
                                 'queryFilters' => [['employee.employee_number', '=', $employee->employee_number]],
                                 'hiddenFields' => ['onTable' => ['employee_id', 'employee_number']],
                             ],
                             key('timeoff-' . $recordId)
                         )
                     @endif

                     {{-- Documents Tab --}}
                     @if ($activeTab === 'documents')
                         <div class="mb-3 d-flex justify-content-end">
                             <button
                                 onclick="Livewire.dispatch('openAddModal', ['hr.document', { employee_id: '{{ $employee->id }}' }])"
                                 class="btn btn-sm btn-primary">
                                 <i class="fas fa-upload"></i> Upload
                             </button>
                         </div>
                         @livewire(
                             'qf.data-table',
                             [
                                 'configKey' => 'hr.document',
                                 'queryFilters' => [['employee.employee_number', '=', $employee->employee_number]],
                                 'hiddenFields' => ['onTable' => ['employee_id', 'employee_number']],
                             ],
                             key('documents-' . $recordId)
                         )
                     @endif

                     {{-- Clock Events Tab --}}
                     @if ($activeTab == 'clockevents')
                         @livewire(
                             'qf.data-table',
                             [
                                 'configKey' => 'hr.clock_event',
                                 'queryFilters' => [['employee.employee_number', '=', $employee->employee_number]],
                                 'hiddenFields' => ['onTable' => ['employee_id', 'employee_number']],
                                 'sort' => ['field' => 'timestamp', 'direction' => 'desc'],
                             ],
                             key('clockevents-' . $recordId)
                         )
                     @endif
                 </div>
             </div>



             {{-- Profile Widget Column (right on desktop, top on mobile) --}}
             <div class="col-12 col-md-3  mb-3 mb-md-0">
                 @livewire(
                     'qf.dashboard',
                     [
                         'configKey' => '', // not used because we provide customWidgets
                         'parameters' => [],
                         'customWidgets' => [
                             'title' => '',
                             'widgets' => [
                                 [
                                     'type' => 'profile_header',
                                     'width' => 12,
                                     'photo_url' => $widgetParams['photo_url'] ?? null,
                                     'full_name' => $widgetParams['full_name'] ?? $fullName,
                                     'employee_number' => $widgetParams['employee_number'] ?? $employee->employee_number,
                                     'title' => $widgetParams['title'] ?? $jobTitle,
                                     'fields' => $widgetParams['fields'] ?? [['label' => 'Department', 'value' => $departmentName], ['label' => 'Status', 'value' => $status], ['label' => 'Hire Date', 'value' => $hireDate ?? '—'], ['label' => 'Manager', 'value' => $position?->manager?->name ?? '—'], ['label' => 'Work Email', 'value' => $employee->email ?? '—']],
                                     'actions' => $widgetParams['actions'] ?? [['label' => 'Edit', 'icon' => 'fas fa-edit', 'event' => 'openEditModal', 'params' => [$configKey, $recordId]]],
                                 ],
                             ],
                             'layout' => ['columns' => 12, 'gutter' => 3],
                         ],
                     ],
                     key('profile-widget-' . $recordId)
                 )
             </div>






         </div>
     </div>

     <style>
         .detail-page-wrapper {
             font-size: 0.95rem;
         }

         .hover-primary:hover {
             color: var(--bs-primary) !important;
         }

         .card {
             border-radius: 12px;
         }

         .nav-tabs .nav-link {
             color: #6c757d;
             font-weight: 500;
         }

         .nav-tabs .nav-link.active {
             color: var(--bs-primary);
             border-bottom: 2px solid var(--bs-primary);
             background: transparent;
         }
     </style>
 </div>
