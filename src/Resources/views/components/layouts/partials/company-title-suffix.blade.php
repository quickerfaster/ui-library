@php
    $currentCompanyName = \Illuminate\Support\Facades\Session::get('current_company_id')
        ? optional(\App\Modules\Admin\Models\Company::find(\Illuminate\Support\Facades\Session::get('current_company_id')))->name
        : null;
    $asBadge = $asBadge ?? false;
@endphp
@if ($currentCompanyName)
    @if ($asBadge)
        <p class="text-sm text-primary fw-medium mb-1">
            <i class="fas fa-building me-1 opacity-6"></i> {{ $currentCompanyName }}
        </p>
    @else
        — {{ $currentCompanyName }}
    @endif
@endif
