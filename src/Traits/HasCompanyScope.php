<?php

namespace QuickerFaster\UILibrary\Traits;

use QuickerFaster\UILibrary\Scopes\CompanyScope;

/**
 * Apply this trait to any Eloquent model that should be automatically
 * scoped by the tenant company column based on the current session company ID.
 *
 * When a company ID is set in the session, all queries automatically filter
 * by that company. When no company is selected, no filtering is applied and
 * all records are visible.
 *
 * Usage:
 *   use HasCompanyScope;
 *
 * To bypass the scope for a specific query:
 *   Model::withoutCompanyScope()->get();
 *   Model::withoutGlobalScope(CompanyScope::class)->get();
 */
trait HasCompanyScope
{
    /**
     * Boot the trait — registers the CompanyScope global scope.
     */
    protected static function bootHasCompanyScope(): void
    {
        static::addGlobalScope(new CompanyScope());
    }
}
