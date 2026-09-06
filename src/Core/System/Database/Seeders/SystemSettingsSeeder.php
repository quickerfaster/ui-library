<?php

namespace QuickerFaster\UILibrary\Core\System\Database\Seeders;

use Illuminate\Database\Seeder;
use QuickerFaster\UILibrary\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'app_name' => env('APP_NAME', 'QuickerFaster'),
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'timezone' => env('APP_TIMEZONE', 'UTC'),
            'language' => 'en',
            'pagination_per_page' => 25,
        ];

        foreach ($defaults as $key => $value) {
            SystemSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general']
            );
        }
    }
}
