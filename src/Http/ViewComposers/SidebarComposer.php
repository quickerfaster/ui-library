<?php

namespace QuickerFaster\UILibrary\Http\ViewComposers;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;
use QuickerFaster\UILibrary\Services\Navigation\NavigationManager;

/**
 * Phase 4.4/4.5: Sidebar View Composer
 *
 * Injects $currentOrganization, $userOrganizations, and $sidebarSections
 * into the sidebar view so the application switcher and config-driven
 * navigation can render.
 *
 * Data sources (in priority order):
 *  1. Session key 'current_company_id' — set by OrganizationSwitchController
 *  2. CompanyProvider::getCurrentCompanyId() — app-specific resolution
 *  3. Fallback: first company from CompanyProvider::getCompanies()
 *
 * Phase 4.5: Also injects $sidebarSections from NavigationManager for
 * config-driven sidebar rendering.
 */
class SidebarComposer
{
    protected CompanyProvider $companyProvider;

    protected NavigationManager $navigationManager;

    public function __construct(CompanyProvider $companyProvider, NavigationManager $navigationManager)
    {
        $this->companyProvider = $companyProvider;
        $this->navigationManager = $navigationManager;
    }

    /**
     * Compose the sidebar view with organization data and navigation sections.
     */
    public function compose(View $view): void
    {
        $user = auth()->user();

        // Resolve organizations for the authenticated user
        $userOrganizations = $this->resolveUserOrganizations($user);

        // Resolve the current organization
        $currentOrganization = $this->resolveCurrentOrganization($user, $userOrganizations);

        // Phase 4.5: Resolve sidebar sections from NavigationManager
        $sidebarSections = $this->navigationManager->getSections();

        $view->with('currentOrganization', $currentOrganization);
        $view->with('userOrganizations', $userOrganizations);
        $view->with('sidebarSections', $sidebarSections);
    }

    /**
     * Resolve all organizations the user belongs to.
     *
     * @param  \Illuminate\Foundation\Auth\User|null $user
     * @return \Illuminate\Support\Collection
     */
    protected function resolveUserOrganizations($user): Collection
    {
        if (!$user) {
            return collect();
        }

        // Try the CompanyProvider contract first (loose coupling)
        try {
            $companies = $this->companyProvider->getCompanies($user);

            if ($companies->isNotEmpty()) {
                return $companies->map(function ($company) {
                    // Normalize to array with id, name, logo keys
                    if (is_array($company)) {
                        return [
                            'id' => $company['id'] ?? null,
                            'name' => $company['name'] ?? 'Unknown',
                            'logo' => $company['logo'] ?? null,
                        ];
                    }

                    if (is_object($company)) {
                        return [
                            'id' => $company->id ?? null,
                            'name' => $company->name ?? 'Unknown',
                            'logo' => $company->logo ?? null,
                        ];
                    }

                    return null;
                })->filter()->values();
            }
        } catch (\Exception $e) {
            // CompanyProvider may not be configured — fall through
        }

        // Fallback: try the user->companies relationship if it exists
        if (method_exists($user, 'companies')) {
            try {
                $companies = $user->companies()->get();

                if ($companies->isNotEmpty()) {
                    return $companies->map(function ($company) {
                        return [
                            'id' => $company->id,
                            'name' => $company->name ?? 'Unknown',
                            'logo' => $company->logo ?? null,
                        ];
                    });
                }
            } catch (\Exception $e) {
                // Relationship may not exist
            }
        }

        return collect();
    }

    /**
     * Resolve the currently active organization.
     *
     * Priority:
     *  1. Session 'current_company_id'
     *  2. CompanyProvider::getCurrentCompanyId()
     *  3. First organization from the user's list
     *
     * @param  \Illuminate\Foundation\Auth\User|null $user
     * @param  \Illuminate\Support\Collection        $userOrganizations
     * @return array|null
     */
    protected function resolveCurrentOrganization($user, Collection $userOrganizations): ?array
    {
        if (!$user) {
            return null;
        }

        // 1. Session-stored company ID (set by OrganizationSwitchController)
        $sessionCompanyId = session('current_company_id');
        if ($sessionCompanyId && $userOrganizations->isNotEmpty()) {
            $match = $userOrganizations->firstWhere('id', $sessionCompanyId);
            if ($match) {
                return $match;
            }
        }

        // 2. CompanyProvider resolution
        try {
            $providerCompanyId = $this->companyProvider->getCurrentCompanyId($user);
            if ($providerCompanyId && $userOrganizations->isNotEmpty()) {
                $match = $userOrganizations->firstWhere('id', $providerCompanyId);
                if ($match) {
                    return $match;
                }
            }
        } catch (\Exception $e) {
            // Provider may not be configured
        }

        // 3. Fallback: first available organization
        if ($userOrganizations->isNotEmpty()) {
            return $userOrganizations->first();
        }

        return null;
    }
}