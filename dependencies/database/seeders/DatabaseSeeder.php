<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Admin\Database\Seeders\QFDatabaseSeeder;
use  Database\Seeders\UserSeeder;
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
        // \App\Models\User::factory(10)->create();
        $this->call([
            QFDatabaseSeeder::class,
            UserSeeder::class
        ]);

        AccessControlPermissionService::seedPermissionNames();

    }
}
