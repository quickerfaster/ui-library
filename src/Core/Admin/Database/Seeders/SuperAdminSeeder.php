<?php

namespace QuickerFaster\UILibrary\Core\Admin\Database\Seeders;

use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@example.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'password');

        $userModel = config('ui-library.user.model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';

        $user = $userModel::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]
        );

        // The InstallCommand ensures the User model has the HasRoles trait
        // before seeders run. The try-catch is a safety net for edge cases,
        // but a failure here must never be silently swallowed — a super admin
        // without a role can lock themselves out of every admin/organization page.
        try {
            if (!$user->hasRole('super_admin')) {
                $user->assignRole('super_admin');
            }
        } catch (\Throwable $e) {
            \Log::error('SuperAdminSeeder: role assignment failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->command->error('   ❌  SuperAdmin role assignment failed: ' . $e->getMessage());
        }
    }
}
