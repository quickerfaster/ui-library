@php
    // Find employee linked to the logged-in user
    //$user = App\Modules\Admin\Models\User::find(Auth::id())->first(); //->firstOrFail();
    if (!auth()->user()) {
        abort(403, 'You are not assigned login record. Please contact HR Office.');
    }

    // $recordId = $user->id;
    $returnParams = []; // no table state needed

@endphp

<x-qf::navigation-layout configKey="hr.employee" context="people" moduleName="hr" :overrides="[
    'top_bar' => ['enabled' => true],
    'breadcrumb' => ['enabled' => false],
    'title' => ['enabled' => false],
    'titleRow' => ['enabled' => false],
    'context_menu' => ['enabled' => false],
]">
    @livewire("qf.data-table-form", ['inline' => false, 'recordId' => auth()->user()->id, 'configKey' => 'admin.user', 'returnParams' => $returnParams])
</x-qf::navigation-layout>
