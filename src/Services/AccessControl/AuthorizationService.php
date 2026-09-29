<?php

namespace QuickerFaster\UILibrary\Services\AccessControl;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;
use QuickerFaster\UILibrary\Services\Approvals\ApprovalGuard;

class AuthorizationService
{
    public function __construct(
        protected ApprovalGuard $approvalGuard,
    ) {}

    /**
     * Callback that resolves the subject ID for a given user.
     *
     * Set by the consuming application in a service provider to enable
     * record-ownership bypass in {@see authorizeView()}. When set, users
     * who own a record (i.e. the record's subject/owner ID matches their
     * resolved subject ID) are allowed to view it without needing the
     * `view_{resource}` Spatie permission.
     *
     * Example (in AppServiceProvider::boot):
     *   \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::\$resolveUserSubjectId =
     *       function (\Illuminate\Contracts\Auth\Authenticatable \$user): ?int {
     *           return \App\Modules\Hr\Models\Employee::where('user_id', \$user->id)->value('id');
     *       };
     *
     * @var callable|null
     */
    public static $resolveUserSubjectId = null;

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
     * @param string $permission  e.g. 'view_users', 'create_record'
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

        // Ownership bypass: users can always view records they own.
        // This covers self-service pages where a subject (e.g. employee)
        // views their own records. The consuming app registers a callback
        // via $resolveUserSubjectId that maps a User to their subject ID.
        if (static::$resolveUserSubjectId !== null) {
            $subjectId = call_user_func(static::$resolveUserSubjectId, $user);

            if ($subjectId !== null && $this->recordBelongsToSubject($record, $subjectId)) {
                return;
            }
        }

        // Workflow approver bypass: users who are the current-step approver
        // for a record's pending workflow are allowed to view it, even if they
        // lack the `view_{resource}` Spatie permission.
        if ($record instanceof Workflowable) {
            $workflow = $record->workflow()->first();

            if ($workflow && $workflow->isPending()) {
                $currentStep = $workflow->currentStep;

                if ($currentStep && $currentStep->isPending()) {
                    $workspaceId = $workflow->context['workspace_id'] ?? null;

                    if ($this->approvalGuard->canApprove(
                        $user,
                        $currentStep->roles ?? [],
                        $workspaceId !== null ? (string) $workspaceId : null
                    )) {
                        return;
                    }
                }
            }
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

        // Subject ownership bypass: subjects can create records
        // scoped to their own identity.
        if (static::$resolveUserSubjectId !== null) {
            $subjectId = call_user_func(static::$resolveUserSubjectId, $user);
            if ($subjectId !== null) {
                return;
            }
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

        // Subject ownership bypass
        if (static::$resolveUserSubjectId !== null) {
            $subjectId = call_user_func(static::$resolveUserSubjectId, $user);
            if ($subjectId !== null) {
                return;
            }
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
     * Check whether a record belongs to a given subject.
     *
     * Supports two patterns:
     *  1. Direct `employee_id` / `subject_id` property/column on the record.
     *  2. `employee()` / `subject()` relationship that returns a model with an `id`.
     *
     * @param object $record     The resolved model instance
     * @param int    $subjectId  The subject's primary key
     * @return bool
     */
    protected function recordBelongsToSubject(object $record, int $subjectId): bool
    {
        // Pattern 1: Direct employee_id / subject_id column
        if (method_exists($record, 'getAttribute') || property_exists($record, 'employee_id')) {
            try {
                $recordSubjectId = $record->getAttribute('employee_id')
                    ?? $record->employee_id
                    ?? null;

                if ($recordSubjectId !== null && (int) $recordSubjectId === $subjectId) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Attribute access failed — fall through to relationship check
            }
        }

        // Pattern 2: employee() / subject() relationship
        if (method_exists($record, 'employee')) {
            try {
                $subject = $record->employee()->first();

                if ($subject && method_exists($subject, 'getKey') && (int) $subject->getKey() === $subjectId) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Relationship access failed
            }
        }

        return false;
    }

    /**
     * Resolve a snake_case resource name from a model class or instance.
     *
     * @param string|object $model  FQCN string or model instance
     * @return string
     */
    protected function resolveResourceName(string|object $model): string
    {
        $class = is_object($model) ? get_class($model) : $model;

        return \Str::snake(class_basename($class));
    }

    /**
     * Get the list of roles the given user is allowed to assign to other users.
     *
     * Used to filter role dropdowns in invitation forms, employee creation,
     * and the access control manager. Prevents privilege escalation by
     * ensuring users can only assign roles at or below their own level.
     *
     * The hierarchy is defined in config('ui-library.role_assignment.hierarchy').
     * Roles not listed in the hierarchy default to config('ui-library.role_assignment.default_assignable').
     *
     * @param Authenticatable|null $user
     * @return array  Associative array of role names (id => name or name => name)
     */
    public static function getAssignableRoles(?Authenticatable $user = null): array
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return [];
        }

        // Super admins and admins can assign any role
        if (static::isBypassAllowed($user)) {
            return \Spatie\Permission\Models\Role::pluck('name', 'id')->toArray();
        }

        $hierarchy = config('ui-library.role_assignment.hierarchy', []);
        $defaultAssignable = config('ui-library.role_assignment.default_assignable', ['employee']);

        // Collect assignable roles from all roles the user has
        $assignableNames = [];
        foreach ($hierarchy as $roleName => $allowedRoles) {
            if ($user->hasRole($roleName)) {
                if ($allowedRoles === ['*']) {
                    return \Spatie\Permission\Models\Role::pluck('name', 'id')->toArray();
                }
                $assignableNames = array_merge($assignableNames, (array) $allowedRoles);
            }
        }

        // If the user has no matching hierarchy entry, use the default
        if (empty($assignableNames)) {
            $assignableNames = (array) $defaultAssignable;
        }

        // Deduplicate and resolve to actual role records
        $assignableNames = array_unique($assignableNames);

        return \Spatie\Permission\Models\Role::whereIn('name', $assignableNames)
            ->pluck('name', 'id')
            ->toArray();
    }
}