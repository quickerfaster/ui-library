

<x-layout>

    @php
        $attendanceId = request()->get('attendance_id') ?? null;
    @endphp

       <livewire:hr.adjust-attendance-mvp :attendanceId="$attendanceId ?? null" />

</x-layouts>


