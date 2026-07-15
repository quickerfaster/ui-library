@php
    $companyId = \Illuminate\Support\Facades\Session::get('current_company_id');
    $isAllCompanies = ($companyId === 0);
    $currentCompanyName = null;

    if ($isAllCompanies) {
        $currentCompanyName = 'All Companies';
    } elseif ($companyId) {
        $currentCompanyName = optional(\App\Modules\Hr\Models\Company::find($companyId))->name;
    }

    $asBadge = $asBadge ?? false;
@endphp
@if ($currentCompanyName)
    @if ($asBadge)
        <p class="text-sm {{ $isAllCompanies ? 'text-info' : 'text-primary' }} fw-medium mb-1">
            <i class="fas {{ $isAllCompanies ? 'fa-globe' : 'fa-building' }} me-1 opacity-6"></i> {{ $currentCompanyName }}
        </p>
    @else
        — {{ $currentCompanyName }}
    @endif
@endif
