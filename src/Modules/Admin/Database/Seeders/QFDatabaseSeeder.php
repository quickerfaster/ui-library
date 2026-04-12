<?php

namespace App\Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;

class QFDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $modulePath = app_path('Modules');

        // Define the current class name as a constant string to avoid any runtime comparison issues
        $excludeClass = '\App\Modules\Admin\Database\Seeders\QFDatabaseSeeder';

        foreach (scandir($modulePath) as $module) {
             if ($module === '.' || $module === '..') continue;

            $seederPath = $modulePath . '/' . $module . '/Database/Seeders';
            if (is_dir($seederPath)) {
                foreach (glob($seederPath . '/*.php') as $file) {
                    $class = "\\App\\Modules\\$module\\Database\\Seeders\\" . basename($file, '.php');

                    // Check if the class exists AND if it is NOT the excluded class
                    if (class_exists($class) && $class !== $excludeClass) {
                        $this->call($class);
                    }
                }
            }
        }
    }
}
