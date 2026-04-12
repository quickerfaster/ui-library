<x-layout>


    @php
        $attensance_id = request()->get('attendance_id') ?? null;
        $employeeId = \App\Modules\Hr\Models\Attendance::where('id', $attensance_id)->first()?->employee_id;
        $employee = \App\Modules\Hr\Models\Employee::findOrFail($employeeId);
        $subPageTitle = 'For ' . $employee->first_name . ' ' . $employee->last_name . ' (' . $employeeId . ')';
    @endphp



    <a href="{{ url()->previous() }}" class="btn bg-gradient-secondary btn-sm my-0">
        <i class="bi bi-arrow-left"></i> &larr; Go Back
    </a>


    <livewire:qf::data-tables.data-table-manager :selectedItemId="$id ?? null" model="App\Modules\Hr\Models\AttendanceSession"
        pageTitle="Work Sessions" :subPageTitle=$subPageTitle :pageQueryFilters="[['attendance_id', '=', $attensance_id]]" :hiddenFields="[
            'onTable' => [
                '0' => 'attendance_id',
                '1' => 'clock_in_event_id',
                '2' => 'clock_out_event_id',
                '3' => 'is_overnight',
                '4' => 'adjusted_by',
                '5' => 'adjusted_at',
                '6' => 'calculated_duration',
                '7' => 'validation_status',
                '8' => 'validation_notes',
            ],
            'onNewForm' => [
                '0' => 'clock_in_event_id',
                '1' => 'clock_out_event_id',
                '2' => 'is_overnight',
                '3' => 'duration_hours',
                '4' => 'adjusted_by',
                '5' => 'adjusted_at',
                '6' => 'calculated_duration',
                '7' => 'validation_status',
                '8' => 'validation_notes',
            ],
            'onEditForm' => [
                '0' => 'clock_in_event_id',
                '1' => 'clock_out_event_id',
                '2' => 'is_overnight',
                '3' => 'adjusted_by',
                '4' => 'adjusted_at',
                '5' => 'calculated_duration',
                '6' => 'validation_status',
                '7' => 'validation_notes',
            ],
            'onQuery' => [],
        ]" />
</x-layouts>
