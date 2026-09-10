@php
    $companyId = \Illuminate\Support\Facades\Session::get('current_company_id');
    $allCompaniesRoles = config('ui-library.all_companies_roles', ['super_admin', 'admin', 'company_admin']);
    $isAllCompanies = ($companyId === 0 && auth()->user()?->hasAnyRole($allCompaniesRoles));
    $currentCompanyName = null;

    if ($isAllCompanies) {
        $currentCompanyName = 'All Companies';
    } elseif ($companyId) {
        $companyProvider = app(\QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider::class);
        $company = $companyProvider->getCompanies(auth()->user())->firstWhere('id', $companyId);
        $currentCompanyName = $company->name ?? null;
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
