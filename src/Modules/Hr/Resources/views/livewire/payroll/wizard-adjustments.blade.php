<div>
    <h4>One‑Time Adjustments</h4>
    <p class="text-muted">
        <i class="fas fa-info-circle"></i>
        Bonuses, commissions, and reimbursements <strong class="text-success">add</strong> to pay.
        Deductions <strong class="text-danger">subtract</strong>.
        Use <strong class="text-warning">+ or –</strong> for corrections.
    </p>

    {{-- Filters --}}
    <div class="row mb-3 g-2">
        <div class="col-md-3">
            <label class="form-label">Company</label>
            <select wire:model.live="filterCompany" class="form-select form-select-sm">
                <option value="">All Companies</option>
                @foreach ($companies as $comp)
                    <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Department</label>
            <select wire:model.live="filterDepartment" class="form-select form-select-sm">
                <option value="">All Departments</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Location</label>
            <select wire:model.live="filterLocation" class="form-select form-select-sm">
                <option value="">All Locations</option>
                @foreach ($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select wire:model.live="filterEmploymentStatus" class="form-select form-select-sm">
                <option value="Active">Active</option>
                <option value="On Leave">On Leave</option>
                <option value="Terminated">Terminated</option>
                <option value="All">All</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Search</label>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm"
                placeholder="Name or Employee #">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <button wire:click="resetFilters" class="btn btn-sm btn-secondary">
                <i class="fas fa-undo-alt"></i> Reset Filters
            </button>
        </div>
    </div>



    <div class="table-responsive">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>Showing {{ $employees->total() }} employees</div>
            @if (!empty($search))
                <div>Search results for: <strong>{{ $search }}</strong></div>
            @endif
        </div>


        <table class="table table-bordered">
            <thead>
                <tr>
                    <th wire:click="sortBy('employee_name')" style="cursor: pointer;">
                        Employee
                        @if ($sortField === 'employee_name')
                            @if ($sortDirection === 'asc')
                                <i class="fas fa-sort-up"></i>
                            @else
                                <i class="fas fa-sort-down"></i>
                            @endif
                        @else
                            <i class="fas fa-sort text-muted"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('base_salary')" style="cursor: pointer;">
                        Base Salary
                        @if ($sortField === 'base_salary')
                            @if ($sortDirection === 'asc')
                                <i class="fas fa-sort-up"></i>
                            @else
                                <i class="fas fa-sort-down"></i>
                            @endif
                        @else
                            <i class="fas fa-sort text-muted"></i>
                        @endif
                    </th>

                    <th>Bonus <span class="text-success">(+)</span></th>
                    <th>Commission <span class="text-success">(+)</span></th>
                    <th>Correction <span class="text-warning">(±)</span></th>
                    <th>Reimbursement <span class="text-success">(+)</span></th>
                    <th>Deduction <span class="text-danger">(–)</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                    <tr wire:key="emp-{{ $emp->employee_id }}">
                        <td>
                            {{ $emp->employee->first_name }} {{ $emp->employee->last_name }}
                            @if ($emp->employee->employee_number)
                                <br><small class="text-muted">#{{ $emp->employee->employee_number }}</small>
                            @endif
                        </td>
                        <td>
                            {{ $this->getCurrencySymbol($emp->salary_currency ?? 'USD') }}{{ number_format($emp->base_salary, 2) }}
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text text-success"><i class="fas fa-plus-circle"></i></span>
                                <input type="number" step="0.01"
                                    wire:model.live.debounce.500ms="tempAdjustments.{{ $emp->employee_id }}.Bonus"
                                    class="form-control form-control-sm" placeholder="0.00">
                            </div>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text text-success"><i class="fas fa-plus-circle"></i></span>
                                <input type="number" step="0.01"
                                    wire:model.live.debounce.500ms="tempAdjustments.{{ $emp->employee_id }}.Commission"
                                    class="form-control form-control-sm" placeholder="0.00">
                            </div>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text text-warning"><i class="fas fa-exchange-alt"></i></span>
                                <input type="number" step="0.01"
                                    wire:model.live.debounce.500ms="tempAdjustments.{{ $emp->employee_id }}.Correction"
                                    class="form-control form-control-sm" placeholder="+100 or -50">
                            </div>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text text-success"><i class="fas fa-plus-circle"></i></span>
                                <input type="number" step="0.01"
                                    wire:model.live.debounce.500ms="tempAdjustments.{{ $emp->employee_id }}.Reimbursement"
                                    class="form-control form-control-sm" placeholder="0.00">
                            </div>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text text-danger"><i class="fas fa-minus-circle"></i></span>
                                <input type="number" step="0.01"
                                    wire:model.live.debounce.500ms="tempAdjustments.{{ $emp->employee_id }}.Deduction"
                                    class="form-control form-control-sm" placeholder="0.00">
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No employees found for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination links --}}
    @if ($employees->hasPages())
        <div class="mt-3">
            {{ $employees->links() }}
        </div>
    @endif




    <div class="alert alert-info mt-3">
        <i class="fas fa-save"></i> Changes are saved automatically. Click <strong>Save & Continue</strong> when done.
    </div>
</div>
