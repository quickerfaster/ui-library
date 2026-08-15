<?php

namespace QuickerFaster\UILibrary\Services\AccessControl;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthorizationService
{
    /**
     * Pipe-separated string of admin role names for use with
     * Spatie's @hasanyrole Blade directive.
     *
     * Usage in Blade:
     *   @hasanyrole(\QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::ADMIN_ROLES)
     */
    const ADMIN_ROLES = 'super_admin|admin|company_admin';

    /**
     * Array of admin role names for use with hasAnyRole() checks.
     *
     * Usage in PHP:
     *   auth()->user()->hasAnyRole(AuthorizationService::ADMIN_ROLES_ARRAY)
     */
    const ADMIN_ROLES_ARRAY = ['super_admin', 'admin', 'company_admin'];

    /**
     * Array of company-level admin roles (super_admin + company_admin).
     */
    const COMPANY_ADMIN_ROLES_ARRAY = ['super_admin', 'company_admin'];

    /**
     * Check if the given user has any admin role (super_admin or admin).
     *
     * This is the central bypass check — super admins and admins
     * are granted access to everything without granular permission checks.
     *
     * @param Authenticatable|null $user
     * @return bool
     */
    public static function isBypassAllowed(?Authenticatable $user): bool
    {
        if (!$user) {
            return false;
        }

        // Primary check: Spatie role-based bypass (super_admin or admin).
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(self::ADMIN_ROLES_ARRAY)) {
            return true;
        }

        // Fallback: the configured super admin email always bypasses.
        // This protects against seed failures where role assignment silently
        // fails, leaving the model_has_roles pivot table empty.
        $superAdminEmail = env('SUPER_ADMIN_EMAIL', 'admin@example.com');
        if (
            $superAdminEmail
            && method_exists($user, 'getAttribute')
            && $user->getAttribute('email') === $superAdminEmail
        ) {
            \Log::debug('[AuthorizationService] super admin email bypass', [
                'email' => $superAdminEmail,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Check if a user can access a given view (page).
     *
     * Super admins and admins bypass all granular permission checks.
     * For other users, the standard Spatie permission check applies.
     *
     * Usage in Blade:
     *   @if(\QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView('view_users'))
     *
     * @param string $permission  e.g. 'view_users', 'create_employee'
     * @param Authenticatable|null $user  Defaults to auth()->user()
     * @return bool
     */
    public static function canAccessView(string $permission, ?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Bypass for super admin / admin
        if (static::isBypassAllowed($user)) {
            return true;
        }

        // Standard Spatie permission check
        if (method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return false;
    }

    /**
     * Authorize that the user can view a record.
     *
     * Super admins and admins bypass all granular permission checks.
     * For other users, the 'view_{resource}' Spatie permission is checked.
     *
     * @param Authenticatable|null $user
     * @param object $record           The resolved model instance
     * @param string|object $modelClass  The model class (FQCN string or instance)
     * @return void
     *
     * @throws AuthorizationException
     */
    public function authorizeView(?Authenticatable $user, object $record, string|object $modelClass): void
    {
        if (!$user) {
            throw new AuthorizationException('Unauthenticated.');
        }

        // Bypass for super admin / admin
        if (static::isBypassAllowed($user)) {
            return;
        }

        $resource = $this->resolveResourceName($modelClass);

        if (method_exists($user, 'can') && $user->can('view_' . $resource)) {
            return;
        }

        throw new AuthorizationException('You are not authorized to view this record.');
    }

    /**
     * Authorize that the user can create a new record.
     *
     * Super admins and admins bypass all granular permission checks.
     * For other users, the 'create_{resource}' Spatie permission is checked.
     *
     * @param Authenticatable|null $user
     * @param string $modelClass  The model FQCN
     * @return void
     *
     * @throws AuthorizationException
     */
    public function authorizeCreate(?Authenticatable $user, string $modelClass): void
    {
        if (!$user) {
            throw new AuthorizationException('Unauthenticated.');
        }

        // Bypass for super admin / admin
        if (static::isBypassAllowed($user)) {
            return;
        }

        $resource = $this->resolveResourceName($modelClass);

        if (method_exists($user, 'can') && $user->can('create_' . $resource)) {
            return;
        }

        throw new AuthorizationException('You are not authorized to create this record.');
    }

    /**
     * Authorize that the user can update a record.
     *
     * Super admins and admins bypass all granular permission checks.
     * For other users, the 'edit_{resource}' Spatie permission is checked.
     *
     * @param Authenticatable|null $user
     * @param object $record           The resolved model instance
     * @param string $modelClass       The model FQCN
     * @return void
     *
     * @throws AuthorizationException
     */
    public function authorizeUpdate(?Authenticatable $user, object $record, string $modelClass): void
    {
        if (!$user) {
            throw new AuthorizationException('Unauthenticated.');
        }

        // Bypass for super admin / admin
        if (static::isBypassAllowed($user)) {
            return;
        }

        // Self-edit bypass: users can always update their own record.
        // This covers self-service pages such as /my-account, where the
        // resolved record is the authenticated user's own model instance.
        if (
            $record instanceof $user
            && method_exists($record, 'getKey')
            && $user->getAuthIdentifier() === $record->getKey()
        ) {
            return;
        }

        $resource = $this->resolveResourceName($modelClass);

        if (method_exists($user, 'can') && $user->can('edit_' . $resource)) {
            return;
        }

        throw new AuthorizationException('You are not authorized to update this record.');
    }

    /**
     * Resolve a kebab-case resource name from a model class or instance.
     *
     * @param string|object $model  FQCN string or model instance
     * @return string
     */
    protected function resolveResourceName(string|object $model): string
    {
        $class = is_object($model) ? get_class($model) : $model;

        return \Str::kebab(class_basename($class));
    }
}