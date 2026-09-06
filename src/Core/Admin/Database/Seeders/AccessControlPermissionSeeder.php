<?php

namespace QuickerFaster\UILibrary\Core\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use QuickerFaster\UILibrary\Services\AccessControl\AccessControlPermissionService;

/**
 * Seeds model-level access-control permission names (view_*, create_*,
 * edit_*, delete_*, print_*, export_*, import_*) for every model discovered
 * across the business modules (app/Modules) and core modules (src/Core).
 *
 * This mirrors the AccessControlPermissionService::seedPermissionNames()
 * call performed by the consuming app's dependencies/database/seeders/
 * DatabaseSeeder, so the single-command install produces the same
 * permission set without requiring the app-level seeder to be published.
 */
class AccessControlPermissionSeeder extends Seeder
{
    public function run(): void
    {
        AccessControlPermissionService::seedPermissionNames();
    }
}
