<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use QuickerFaster\UILibrary\Core\Admin\Database\Seeders\RoleSeeder;
use QuickerFaster\UILibrary\Core\Admin\Database\Seeders\UserSeeder;
use QuickerFaster\UILibrary\Services\AccessControl\AccessControlPermissionService;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        AccessControlPermissionService::seedPermissionNames();
    }
}
