<?php

namespace QuickerFaster\UILibrary\Contracts\Navigation;

use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\User;

interface CompanyProvider
{
    /**
     * Get all companies available to the given user.
     *
     * @param User|null $user
     * @return Collection Collection of company objects (must have at least: id, name)
     */
    public function getCompanies(?User $user): Collection;

    /**
     * Get the current company ID for the given user.
     * Return null if no company is selected or available.
     *
     * @param User|null $user
     * @return int|null
     */
    public function getCurrentCompanyId(?User $user): ?int;
}
