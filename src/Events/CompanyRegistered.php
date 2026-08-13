<?php

namespace QuickerFaster\UILibrary\Events;

use QuickerFaster\UILibrary\Core\Organization\Models\Company;

/**
 * Fired when a new company is registered via RegistrationController.
 *
 * Consuming applications should listen for this event to create
 * domain-specific defaults (e.g., shifts, attendance policies,
 * work patterns, etc.).
 */
class CompanyRegistered
{
    public function __construct(
        public Company $company,
    ) {}
}
