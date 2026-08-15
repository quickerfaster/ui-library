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
                'role' => 'super_admin',
            ],
            [
                'email' => 'admin@test.com',
                'name' => 'Test Admin',
                'password' => 'password',
                'role' => 'admin',
            ],
            [
                'email' => 'company.admin@example.com',
                'name' => 'Company Admin',
                'password' => 'password',
                'role' => 'company_admin',
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

            $role = $data['role'] ?? 'super_admin';

            // The InstallCommand ensures the User model has the HasRoles trait
            // before seeders run. The try-catch is a safety net for edge cases,
            // but a failure here must never be silently swallowed — an admin
            // without a role can lock themselves out of every admin/organization page.
            try {
                if (!$user->hasRole($role)) {
                    $user->assignRole($role);
                }
            } catch (\Throwable $e) {
                \Log::error('UserSeeder: role assignment failed', [
                    'email' => $data['email'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->command->error('   ❌  UserSeeder role assignment failed for ' . $data['email'] . ': ' . $e->getMessage());
            }
        }
    }
}