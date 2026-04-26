@php
    // Find employee linked to the logged-in user
    $employee = App\Modules\Hr\Models\Employee::where('user_id', Auth::id())->first(); //->firstOrFail();
    if (!$employee) {
        abort(403, 'You are not assigned login record. Please contact HR.');
    }

    $recordId = $employee->id;
    $returnParams = []; // no table state needed
    $customComponent = 'qf.employee-detail';

@endphp

<x-qf::navigation-layout configKey="hr.employee" context="people" moduleName="hr" :overrides="[
    'top_bar' => ['enabled' => true],
    'breadcrumb' => ['enabled' => false],
    'title' => ['enabled' => false],
    'titleRow' => ['enabled' => false],
    'context_menu' => ['enabled' => false],
]">
    @livewire($customComponent, ['inline' => true, 'recordId' => $recordId, 'configKey' => 'hr.employee', 'returnParams' => $returnParams])
</x-qf::navigation-layout>
