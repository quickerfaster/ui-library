<?php

namespace QuickerFaster\UILibrary\Services\Navigation;

use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\User;

class NullCompanyProvider implements CompanyProvider
{
    public function getCompanies(?User $user): Collection
    {
        return collect();
    }

    public function getCurrentCompanyId(?User $user): ?int
    {
        return null;
    }
}
