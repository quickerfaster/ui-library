<?php

namespace QuickerFaster\UILibrary\Core\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@example.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'password');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]
        );

        if (!$user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }
    }
}
