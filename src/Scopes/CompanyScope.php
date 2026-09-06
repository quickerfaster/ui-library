<?php

namespace QuickerFaster\UILibrary\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Session;

/**
 * Global scope that automatically filters Eloquent queries by the tenant's
 * company column whenever a current company ID is present in the session.
 *
 * The tenant column name and session key are configurable via:
 *
 *   config('ui-library.tenancy.column', 'company_id')
 *   config('ui-library.tenancy.session_key', 'current_company_id')
 *
 * This is the domain-agnostic tenant term used across the library.
 */
class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Only filters when a current company ID is set in the session.
     * Users without a selected company see all data.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $sessionKey = (string) config('ui-library.tenancy.session_key', 'current_company_id');
        $column = (string) config('ui-library.tenancy.column', 'company_id');

        $companyId = Session::get($sessionKey);

        if ($companyId) {
            $table = $model->getTable();
            $builder->where("{$table}.{$column}", $companyId);
        }
    }

    /**
     * Extend the query builder with a method to bypass the company scope.
     *
     * Usage: Model::withoutCompanyScope()->get()
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutCompanyScope', function (Builder $builder) {
            return $builder->withoutGlobalScope(static::class);
        });
    }
}
