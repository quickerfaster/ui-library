<?php

namespace QuickerFaster\UILibrary\Services\Navigation;

use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;
use QuickerFaster\UILibrary\Core\Organization\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Auth\User;

/**
 * Default implementation of CompanyProvider that works with the library's
 * own Organization\Models\Company model.
 *
 * This provider:
 *  - Returns companies via the user's companies() relationship if available,
 *    otherwise falls back to all active companies.
 *  - Resolves the current company ID from the session.
 *
 * Consuming applications can override this by binding their own
 * CompanyProvider implementation in their AppServiceProvider.
 */
class DefaultCompanyProvider implements CompanyProvider
{
    /**
     * Get all companies available to the given user.
     *
     * @param User|null $user
     * @return Collection Collection of company objects (must have at least: id, name)
     */
    public function getCompanies(?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        // 1. Try the user's companies() relationship (common in multi-tenant apps)
        if (method_exists($user, 'companies')) {
            try {
                $companies = $user->companies()->get();

                if ($companies->isNotEmpty()) {
                    return $companies;
                }
            } catch (\Exception $e) {
                // Relationship may not exist or may throw — fall through
            }
        }

        // 2. Fallback: return all companies from the database.
        //    We avoid filtering by a specific column (e.g. is_active) because
        //    consuming apps may create the companies table with different
        //    column names (e.g. 'status' instead of 'is_active'). The library
        //    migration adds is_active, but it only runs if the table doesn't
        //    already exist — which it won't when a consuming app's migration
        //    creates it first.
        try {
            $companies = Company::orderBy('name')->get();

            if ($companies->isNotEmpty()) {
                return $companies;
            }
        } catch (\Throwable $e) {
            // Table may not exist yet (e.g., during fresh install) — fall through.
            // Catching \Throwable handles both \Exception and \Error (e.g. PDOException
            // when the table is missing).
        }

        // 3. Ultimate fallback: return a synthetic "Default Company" so the
        //    company switcher renders even when no companies exist yet.
        //    This ensures the library works standalone without pre-seeded data.
        return collect([
            (object) [
                'id' => 1,
                'name' => 'Default Company',
                'is_active' => true,
            ],
        ]);
    }

    /**
     * Get the current company ID for the given user.
     *
     * Resolves from session first, then falls back to the first available company.
     *
     * @param User|null $user
     * @return int|null
     */
    public function getCurrentCompanyId(?User $user): ?int
    {
        // 1. Session takes priority
        $sessionCompanyId = Session::get('current_company_id');

        if ($sessionCompanyId !== null) {
            return (int) $sessionCompanyId;
        }

        // 2. Fall back to the first available company
        if ($user) {
            $companies = $this->getCompanies($user);

            if ($companies->isNotEmpty()) {
                $first = $companies->first();
                $id = is_array($first) ? ($first['id'] ?? null) : ($first->id ?? null);

                if ($id !== null) {
                    Session::put('current_company_id', (int) $id);
                    return (int) $id;
                }
            }
        }

        return null;
    }
}