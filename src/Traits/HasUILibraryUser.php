<?php

namespace QuickerFaster\UILibrary\Traits;

/**
 * Meta-trait that bundles all required library behaviours for the consuming
 * application's User model.
 *
 * A consuming app only needs to add `use HasUILibraryUser;` to its User
 * model — this trait internally composes every other library trait that
 * the User model is expected to provide.
 *
 * New trait requirements are added here so that existing apps get them
 * automatically after a composer update (provided they re-run the install
 * command, which injects `HasUILibraryUser` if missing).
 *
 * ## Example
 *
 * ```php
 * // app/Models/User.php
 * use QuickerFaster\UILibrary\Traits\HasUILibraryUser;
 *
 * class User extends Authenticatable
 * {
 *     use HasUILibraryUser;
 *     // ... rest of model
 * }
 * ```
 */
trait HasUILibraryUser
{
    use HasSettings;
    use HasNotifications;

    /**
     * Columns the library manages on the consuming app's User model that must
     * be mass-assignable for DataTableForm's update()/create() calls to persist.
     *
     * - `status`: the admin user active/inactive flag.
     * - `company_id`: the multi-tenancy foreign key.
     */
    public const REQUIRED_FILLABLE = ['status', 'company_id'];

    /**
     * Boot the trait and merge the required fillable columns into the model.
     *
     * Laravel automatically invokes initialize{TraitName}() when an Eloquent
     * model using this trait is booted. DataTableForm saves records through
     * $record->update()/$record->create(), which respect Eloquent's $fillable
     * guards — so any form field not present in $fillable is silently dropped.
     * Merging these columns here guarantees they are mass-assignable in every
     * consuming app, without requiring each app to remember to update its own
     * $fillable declaration.
     */
    protected function initializeHasUILibraryUser(): void
    {
        $this->fillable = array_values(array_unique(array_merge(
            $this->fillable,
            self::REQUIRED_FILLABLE
        )));
    }

    // Future traits to be composed here:
    // use HasDashboardPreferences;
}