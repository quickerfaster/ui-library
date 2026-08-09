<?php

namespace QuickerFaster\UILibrary\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class QuickerFasterInstallUI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quicker-faster-ui:install {--force : Force overwrite existing files}';
    protected $force;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quick installation setup for Laravel project';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting QuickerFaster Installation...');
        $this->force = $this->option('force');

        if (app()->runningInConsole()) {
            $this->publishVendorFiles();
            $this->runMigrations();
            $this->seedRoles();
            $this->createSuperAdmin();
            $this->createStorageLink();
            $this->generateAppKey();
            $this->clearCaches();
        }

        $this->info('✅ QuickerFaster installation completed successfully!');
        $this->info('📧 Super Admin: ' . env('SUPER_ADMIN_EMAIL', 'admin@example.com'));
        $this->info('🔑 You can now log in at: ' . url('/login'));
    }

    protected function publishVendorFiles()
    {
        $this->info('📁 Publishing vendor files...');

        // Publish package assets
        Artisan::call('vendor:publish', ['--tag' => 'ui-library-assets', '--force' => true]);
        Artisan::call('vendor:publish', ['--tag' => 'ui-library-config', '--force' => true]);

        // Livewire publish
        Artisan::call('vendor:publish', ['--tag' => 'livewire:assets', '--force' => true]);
        Artisan::call('vendor:publish', ['--tag' => 'livewire:config', '--force' => true]);

        // Fortify
        Artisan::call('vendor:publish', ['--provider' => 'Laravel\Fortify\FortifyServiceProvider']);

        // Spatie Permission
        Artisan::call('vendor:publish', ['--provider' => 'Spatie\Permission\PermissionServiceProvider']);

        $this->info('✅ Vendor files published successfully!');
    }

    protected function runMigrations()
    {
        $this->info('🗃️ Running migrations...');

        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Migrations completed.');
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
        }
    }

    protected function seedRoles()
    {
        $this->info('👥 Seeding default roles...');

        try {
            Artisan::call('db:seed', ['--class' => 'QuickerFaster\\UILibrary\\Core\\Admin\\Database\\Seeders\\RoleSeeder']);
            $this->info('✅ Roles seeded: super_admin, admin, user');
        } catch (\Exception $e) {
            $this->warn('Role seeding failed: ' . $e->getMessage());
        }
    }

    protected function createSuperAdmin()
    {
        $this->info('👤 Creating super admin user...');

        $email = $this->ask('Super admin email', env('SUPER_ADMIN_EMAIL', 'admin@example.com'));
        $password = $this->secret('Super admin password (min 8 chars)');

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return;
        }

        try {
            $user = \App\Models\User::firstOrCreate(
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

            $this->info("✅ Super admin created: {$email}");
        } catch (\Exception $e) {
            $this->error('Failed to create super admin: ' . $e->getMessage());
        }
    }

    protected function createStorageLink()
    {
        $this->info('🔗 Creating storage link...');

        if (file_exists(public_path('storage'))) {
            $this->info('Storage link already exists.');
            return;
        }

        try {
            Artisan::call('storage:link');
            $this->info('✅ Storage link created.');
        } catch (\Exception $e) {
            $this->error('Failed to create storage link: ' . $e->getMessage());
        }
    }

    protected function generateAppKey()
    {
        if (empty(config('app.key'))) {
            $this->info('🔑 Generating application key...');
            Artisan::call('key:generate');
            $this->info('✅ Application key generated.');
        }
    }

    protected function clearCaches()
    {
        $this->info('🧹 Clearing caches...');

        $commands = ['cache:clear', 'config:clear', 'route:clear', 'view:clear'];

        foreach ($commands as $command) {
            try {
                Artisan::call($command);
            } catch (\Exception $e) {
                // Silently continue
            }
        }

        $this->info('✅ Caches cleared.');
    }

    public function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $dir = opendir($source);

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            if (is_dir($sourcePath)) {
                // Recursively copy subdirectories
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                // Copy files (overwrites by default)
                copy($sourcePath, $destPath);
            }
        }

        closedir($dir);
        return true;
    }
}
