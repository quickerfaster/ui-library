<?php

namespace QuickerFaster\UILibrary\Core\Admin\Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $userModel = config('ui-library.user.model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';

        $users = [
            [
                'email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com'),
                'name' => 'Super Admin',
                'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
            ],
            [
                'email' => 'admin@test.com',
                'name' => 'Test Admin',
                'password' => 'password',
            ],
        ];

        foreach ($users as $data) {
            $user = $userModel::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt($data['password']),
                    'email_verified_at' => now(),
                ]
            );

            // The InstallCommand ensures the User model has the HasRoles trait
            // before seeders run. The try-catch is a safety net for edge cases.
            try {
                if (!$user->hasRole('super_admin')) {
                    $user->assignRole('super_admin');
                }
            } catch (\Exception $e) {
                $this->command->warn('   ⚠️  UserSeeder role assignment failed for ' . $data['email'] . ': ' . $e->getMessage());
            }
        }
    }
}