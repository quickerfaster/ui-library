<?php

namespace QuickerFaster\UILibrary\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;

/**
 * Phase 4.4: Organization Switch Controller
 *
 * Handles switching the active organization/company for the authenticated user.
 * Stores the selected company ID in the session and redirects back.
 */
class OrganizationSwitchController
{
    protected CompanyProvider $companyProvider;

    public function __construct(CompanyProvider $companyProvider)
    {
        $this->companyProvider = $companyProvider;
    }

    /**
     * Switch the active organization for the authenticated user.
     *
     * @param  Request  $request
     * @param  mixed    $companyId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request, $companyId): RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            Log::warning('OrganizationSwitchController: Unauthenticated user attempted to switch company.', [
                'company_id' => $companyId,
            ]);
            return redirect()->route(config('ui-library.home_route', 'admin.dashboard'));
        }

        // Validate the user belongs to this company
        if (!$this->userBelongsToCompany($user, $companyId)) {
            Log::warning('OrganizationSwitchController: User attempted to switch to unauthorized company.', [
                'user_id' => $user->id,
                'company_id' => $companyId,
            ]);
            return redirect()->back()->withErrors([
                'company' => __('You do not have access to this organization.'),
            ]);
        }

        // Store the selected company ID in the session
        session(['current_company_id' => (int) $companyId]);

        Log::info('OrganizationSwitchController: User switched company.', [
            'user_id' => $user->id,
            'company_id' => $companyId,
        ]);

        // Redirect back to the previous page, or to the dashboard
        $intended = $request->input('redirect_to');
        if ($intended) {
            return redirect()->to($intended);
        }

        return redirect()->back(303);
    }

    /**
     * Check whether the authenticated user belongs to the given company.
     *
     * Uses the CompanyProvider contract for loose coupling. Falls back to
     * checking a `companies` relationship on the User model if available.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  mixed                             $companyId
     * @return bool
     */
    protected function userBelongsToCompany($user, $companyId): bool
    {
        // 1. Try CompanyProvider
        try {
            $companies = $this->companyProvider->getCompanies($user);

            if ($companies->isNotEmpty()) {
                return $companies->contains(function ($company) use ($companyId) {
                    $id = is_array($company) ? ($company['id'] ?? null) : ($company->id ?? null);
                    return $id !== null && (int) $id === (int) $companyId;
                });
            }
        } catch (\Exception $e) {
            // Provider may not be configured — fall through
        }

        // 2. Fallback: check user->companies relationship
        if (method_exists($user, 'companies')) {
            try {
                return $user->companies()->where('id', $companyId)->exists();
            } catch (\Exception $e) {
                // Relationship may not exist
            }
        }

        // 3. If no validation method is available, allow the switch
        // (consuming apps should configure a CompanyProvider for proper validation)
        return true;
    }
}