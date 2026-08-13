<?php

namespace QuickerFaster\UILibrary\Http\Controllers;

use QuickerFaster\UILibrary\Core\Organization\Models\Company;
use QuickerFaster\UILibrary\Core\Organization\Models\Location;
use QuickerFaster\UILibrary\Core\Organization\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Company registration controller for multi-tenant SaaS setups.
 *
 * This controller creates a new company with default organizational structure.
 * Domain-specific defaults (shifts, attendance policies, work patterns, etc.)
 * should be provided by the consuming application via event listeners or by
 * extending this controller.
 *
 * TODO: Implement fully when SaaS multi-tenancy is ready.
 */
class RegistrationController extends Controller
{
    public function register(Request $request)
    {
        DB::transaction(function () use ($request) {
            // 1. Create the company
            $company = Company::create([
                'name' => $request->company_name,
                'subdomain' => $request->subdomain,
                'status' => 'active',
                // Add other required fields as per your model
            ]);

            // 2. Create a default location and department
            $defaultLocation = Location::create([
                'company_id' => $company->id,
                'name' => 'Headquarters',
                'code' => 'HQ',
                'is_active' => true,
                'is_headquarters' => true,
                'city' => 'Default City',
                'country' => 'US',
                'timezone' => 'America/New_York',
            ]);

            $defaultDepartment = Department::create([
                'company_id' => $company->id,
                'name' => 'General',
                'code' => 'GEN',
                'is_active' => true,
            ]);

            // Dispatch an event so consuming applications can hook in
            // and create domain-specific defaults (shifts, policies, etc.)
            event(new \QuickerFaster\UILibrary\Events\CompanyRegistered($company));
        });

        // Return appropriate response (e.g., redirect to dashboard)
        return redirect()->route('dashboard')->with('success', 'Company registered successfully.');
    }
}