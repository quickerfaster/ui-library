<?php

namespace QuickerFaster\UILibrary\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use QuickerFaster\UILibrary\Console\Support\UserModelTraitInjector;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ui-library:install
                            {--no-auth : Skip auth scaffolding and User model modifications}
                            {--no-seed : Skip database seeding}
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Single-command installation for QuickerFaster UI Library';

    /**
     * Track overall success state.
     */
    protected bool $hasErrors = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting QuickerFaster UI Library Installation...');
        $this->newLine();

        $this->publishConfig();
        $this->publishViews();
        $this->publishMigrations();
        $this->publishAssets();
        $this->publishVendorProviders();
        $this->deduplicateMigrations();
        $this->runMigrations();
        $this->configureUserModel();
        $this->ensureUserHasLibraryTraits();
        $this->updateUserDataConfig();
        $this->runSeeders();
        $this->scaffoldAuth();
        $this->createStorageLink();
        $this->generateAppKey();
        $this->clearCaches();
        $this->verifyInstallation();

        $this->newLine();

        if ($this->hasErrors) {
            $this->warn('⚠️  QuickerFaster UI Library installed with some warnings. Review output above.');
            return self::FAILURE;
        }

        $this->info('✅ QuickerFaster UI Library installed successfully!');
        $this->info('📧 Default admin: ' . env('SUPER_ADMIN_EMAIL', 'admin@example.com'));
        $this->info('🔑 You can log in at: ' . url('/login'));

        return self::SUCCESS;
    }

    /**
     * Publish the ui-library config file.
     */
    protected function publishConfig(): void
    {
        $this->info('📁 Publishing config...');

        try {
            Artisan::call('vendor:publish', [
                '--tag' => 'ui-library-config',
                '--force' => true,
            ]);
            $this->info('   ✅ Config published.');
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Config publish failed: ' . $e->getMessage());
            $this->hasErrors = true;
        }
    }

    /**
     * Publish the ui-library views.
     */
    protected function publishViews(): void
    {
        $this->info('📁 Publishing views...');

        try {
            Artisan::call('vendor:publish', [
                '--tag' => 'ui-library-views',
                '--force' => true,
            ]);
            $this->info('   ✅ Views published.');
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Views publish failed: ' . $e->getMessage());
            $this->hasErrors = true;
        }
    }

    /**
     * Publish the ui-library migrations.
     */
    protected function publishMigrations(): void
    {
        $this->info('📁 Publishing migrations...');

        try {
            Artisan::call('vendor:publish', [
                '--tag' => 'ui-library-migrations',
                '--force' => true,
            ]);
            $this->info('   ✅ Migrations published.');
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Migrations publish failed: ' . $e->getMessage());
            $this->hasErrors = true;
        }
    }

    /**
     * Publish the ui-library assets (CSS/JS/images).
     */
    protected function publishAssets(): void
    {
        $this->info('📁 Publishing assets (CSS/JS)...');

        try {
            Artisan::call('vendor:publish', [
                '--tag' => 'ui-library-assets',
                '--force' => true,
            ]);
            $this->info('   ✅ Assets published.');
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Assets publish failed: ' . $e->getMessage());
            $this->hasErrors = true;
        }
    }

    /**
     * Publish required vendor provider files (Livewire, Fortify, Spatie Permission).
     */
    protected function publishVendorProviders(): void
    {
        $this->info('📁 Publishing vendor provider files...');

        // Livewire assets and config
        try {
            Artisan::call('vendor:publish', [
                '--tag' => 'livewire:assets',
                '--force' => true,
            ]);
            Artisan::call('vendor:publish', [
                '--tag' => 'livewire:config',
                '--force' => true,
            ]);
            $this->info('   ✅ Livewire assets/config published.');
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Livewire publish failed: ' . $e->getMessage());
        }

        // Fortify service provider
        try {
            Artisan::call('vendor:publish', [
                '--provider' => 'Laravel\Fortify\FortifyServiceProvider',
            ]);
            $this->info('   ✅ Fortify provider published.');
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Fortify publish failed: ' . $e->getMessage());
        }

        // Spatie Permission service provider (migrations + config)
        try {
            Artisan::call('vendor:publish', [
                '--provider' => 'Spatie\Permission\PermissionServiceProvider',
            ]);
            $this->info('   ✅ Spatie Permission provider published.');
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Spatie Permission publish failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove duplicate migration files that may have been published
     * by vendor providers (e.g., Fortify's two_factor columns, Passkeys).
     * Keeps the earliest timestamped version and removes later duplicates.
     */
    protected function deduplicateMigrations(): void
    {
        $this->info('🔍 Checking for duplicate migrations...');

        $migrationsPath = database_path('migrations');

        if (!File::isDirectory($migrationsPath)) {
            return;
        }

        $files = File::files($migrationsPath);
        $seen = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();

            // Extract the descriptive part after the timestamp (e.g., "add_two_factor_columns_to_users_table")
            if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $filename, $matches)) {
                $baseName = $matches[1];

                if (isset($seen[$baseName])) {
                    // This is a duplicate — keep the earlier one, remove this one
                    $existingTimestamp = $seen[$baseName]['timestamp'];
                    $thisTimestamp = substr($filename, 0, 17);

                    if ($thisTimestamp > $existingTimestamp) {
                        // Current file is newer — remove it
                        File::delete($file->getPathname());
                        $this->warn("   🧹 Removed duplicate migration: {$filename}");
                    } else {
                        // Current file is older — remove the previously seen one
                        File::delete($seen[$baseName]['path']);
                        $this->warn("   🧹 Removed duplicate migration: {$seen[$baseName]['filename']}");
                        $seen[$baseName] = [
                            'timestamp' => $thisTimestamp,
                            'path' => $file->getPathname(),
                            'filename' => $filename,
                        ];
                    }
                } else {
                    $seen[$baseName] = [
                        'timestamp' => substr($filename, 0, 17),
                        'path' => $file->getPathname(),
                        'filename' => $filename,
                    ];
                }
            }
        }

        $this->info('   ✅ Migration deduplication complete.');
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): void
    {
        $this->info('🗃️  Running database migrations...');

        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('   ✅ Migrations completed.');
        } catch (\Exception $e) {
            $this->error('   ❌ Migration failed: ' . $e->getMessage());
            $this->hasErrors = true;
        }
    }

    /**
     * Resolve the User model FQCN, write it to the published config,
     * and set it in the environment.
     *
     * Skipped when --no-auth is passed.
     */
    protected function configureUserModel(): void
    {
        if ($this->option('no-auth')) {
            $this->info('⏭️  Skipping User model configuration (--no-auth flag).');
            return;
        }

        $this->info('🔧 Configuring User model...');

        // 1. Determine target model
        $userModel = config('auth.providers.users.model')
            ?? 'App\\Models\\User';

        // 2. Verify the model class exists
        if (!class_exists($userModel)) {
            $this->warn("   ⚠️  User model '{$userModel}' not found.");
            $this->warn('   The library will fall back to the default (App\\Models\\User).');
            $this->warn('   Run `php artisan ui-library:install` again after creating your User model.');
            $this->hasErrors = true;
            return;
        }

        // 3. Write to the published ui-library config
        $configPath = config_path('ui-library.php');
        if (File::exists($configPath)) {
            $config = File::get($configPath);

            // Replace the 'model' entry inside the 'user' array
            // Scoped regex: only matches 'model' within the 'user' => [...] block
            $config = preg_replace(
                "/('user'\s*=>\s*\[.*?'model'\s*=>\s*)[^\n]+/s",
                "\$1'{$userModel}',",
                $config,
                1
            );

            File::put($configPath, $config);
        }

        // 4. Set in .env for persistence across config cache clears
        $this->setEnvValue('UI_LIBRARY_USER_MODEL', $userModel);

        // 5. Also set in runtime config
        config()->set('ui-library.user.model', $userModel);

        $this->info("   ✅ User model configured: {$userModel}");
    }

    /**
     * Ensure the resolved User model has the required library traits
     * (HasUILibraryUser and HasRoles).
     *
     * Uses token-based source manipulation instead of fragile regex, so it
     * works regardless of the parent class name (Laravel's default User model
     * extends `Authenticatable`, whose basename is also `User`), whitespace,
     * existing imports and grouped trait `use` statements.
     *
     * Skipped when --no-auth is passed.
     */
    protected function ensureUserHasLibraryTraits(): void
    {
        if ($this->option('no-auth')) {
            $this->info('⏭️  Skipping User model trait injection (--no-auth flag).');
            return;
        }

        $this->info('🔍 Ensuring User model has required library traits...');

        $userModel = config('ui-library.user.model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';

        if (!class_exists($userModel)) {
            $this->warn('   ⚠️  Cannot locate User model to inject traits.');
            $this->warn("   Expected: {$userModel}");
            return;
        }

        $reflection = new \ReflectionClass($userModel);
        $filePath = $reflection->getFileName();

        // Check if file is in vendor/ (package-provided) — skip injection
        if (str_contains($filePath, base_path('vendor'))) {
            $this->info('   ⚠️  User model is vendor-provided. Skipping trait injection.');
            return;
        }

        if (!File::exists($filePath)) {
            $this->warn("   ⚠️  User model file not found at: {$filePath}");
            return;
        }

        $className = class_basename($userModel);
        $requiredTraits = config('ui-library.user.required_traits', [
            \QuickerFaster\UILibrary\Traits\HasUILibraryUser::class,
            \Spatie\Permission\Traits\HasRoles::class,
        ]);

        $traits = [];
        foreach ((array) $requiredTraits as $trait) {
            if (!is_string($trait)) {
                continue;
            }

            if (!trait_exists($trait)) {
                $this->warn("   ⚠️  Required trait not found, skipping: {$trait}");
                continue;
            }

            $traits[] = $trait;
        }

        $result = UserModelTraitInjector::inject(File::get($filePath), $className, $traits);

        if (!empty($result['error'])) {
            $this->warn("   ⚠️  Could not automatically add required traits to {$userModel}.");
            $this->warn('       ' . $result['error']);
            $this->warn('       Please add them manually:');
            $this->warn('');
            foreach ($traits as $trait) {
                $this->warn('           use ' . ltrim($trait, '\\') . ';');
            }
            $this->warn('');
            $this->warn('       class ' . $className . ' extends Authenticatable');
            $this->warn('       {');
            $this->warn('           use ' . implode(', ', array_map('class_basename', $traits)) . ';');
            $this->hasErrors = true;
            return;
        }

        if (!$result['modified']) {
            $this->info('   ✅ All required traits already present.');
            return;
        }

        if (File::put($filePath, $result['contents']) === false) {
            $this->error('   ❌ Failed to write User model file. Please add the traits manually.');
            $this->hasErrors = true;
            return;
        }

        // Re-read the written file and verify the class body actually uses the
        // required traits (the in-memory class may be stale during install).
        $written = File::get($filePath);
        $stillMissing = [];
        foreach ($traits as $trait) {
            if (!UserModelTraitInjector::usesTrait($written, $className, $trait)) {
                $stillMissing[] = class_basename($trait);
            }
        }

        if (!empty($stillMissing)) {
            $this->warn('   ⚠️  Some traits were not added to the User model: ' . implode(', ', $stillMissing));
            $this->warn('       Please add them manually:');
            $this->warn('');
            foreach ($traits as $trait) {
                $this->warn('           use ' . ltrim($trait, '\\') . ';');
            }
            $this->warn('');
            $this->warn('       class ' . $className . ' extends Authenticatable');
            $this->warn('       {');
            $this->warn('           use ' . implode(', ', array_map('class_basename', $traits)) . ';');
            $this->hasErrors = true;
            return;
        }

        $this->info('   ✅ Library traits injected into User model.');
    }

    /**
     * Create an app-level override for the admin.user data config
     * if the consuming app doesn't already have one.
     */
    protected function updateUserDataConfig(): void
    {
        $this->info('🔧 Checking admin.user data config...');

        $targetDir = app_path('Modules/Admin/Data');
        $targetFile = $targetDir . '/user.php';

        // Don't overwrite if consuming app has already published/customised it
        if (File::exists($targetFile)) {
            $this->info('   ✅ App-level admin.user config already exists. Skipping.');
            return;
        }

        $userModel = config('ui-library.user.model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';

        // Check if ModelConfigRepository looks up from app/Modules/Admin/Data/user.php
        // If so, create a minimal override. Otherwise, skip — the library default works.
        $libraryUserConfig = __DIR__ . '/../../Core/Admin/Data/user.php';

        if (!File::exists($libraryUserConfig)) {
            $this->info('   ℹ️  No library user data config found. Skipping.');
            return;
        }

        // Copy the full library config as the app-level override, with corrected model
        File::ensureDirectoryExists($targetDir, 0755, true);

        $content = File::get($libraryUserConfig);
        $content = preg_replace(
            "/('model'\s*=>\s*)[^\n]+/",
            "\$1'{$userModel}',",
            $content,
            1
        );

        File::put($targetFile, $content);
        $this->info("   ✅ Created app-level override: {$targetFile}");
    }

    /**
     * Run library seeders.
     */
    protected function runSeeders(): void
    {
        if ($this->option('no-seed')) {
            $this->info('⏭️  Skipping database seeding (--no-seed flag).');
            return;
        }

        $this->info('🌱 Seeding database...');

        $seeders = [
            'RoleSeeder' => 'QuickerFaster\UILibrary\Core\Admin\Database\Seeders\RoleSeeder',
            'SuperAdminSeeder' => 'QuickerFaster\UILibrary\Core\Admin\Database\Seeders\SuperAdminSeeder',
            'UserSeeder' => 'QuickerFaster\UILibrary\Core\Admin\Database\Seeders\UserSeeder',
            'SystemSettingsSeeder' => 'QuickerFaster\UILibrary\Core\System\Database\Seeders\SystemSettingsSeeder',
            'OrganizationSeeder' => 'QuickerFaster\UILibrary\Core\Organization\Database\Seeders\OrganizationSeeder',
            'NotificationTemplateSeeder' => 'QuickerFaster\UILibrary\Core\Common\Database\Seeders\NotificationTemplateSeeder',
            'AccessControlPermissionSeeder' => 'QuickerFaster\UILibrary\Core\Admin\Database\Seeders\AccessControlPermissionSeeder',
        ];

        foreach ($seeders as $name => $class) {
            try {
                // Run each seeder in a separate PHP process so that any
                // source-file modifications made earlier in the install
                // (e.g. injecting the HasRoles trait into the User model)
                // are picked up by the freshly-booted process.
                $process = new Process([
                    PHP_BINARY,
                    base_path('artisan'),
                    'db:seed',
                    '--class=' . $class,
                    '--force',
                ]);
                $process->setWorkingDirectory(base_path());
                $process->run();

                if (!$process->isSuccessful()) {
                    $this->warn("   ⚠️  {$name} failed: " . trim($process->getErrorOutput()));
                } else {
                    $this->info("   ✅ {$name} seeded.");
                }
            } catch (\Exception $e) {
                $this->warn("   ⚠️  {$name} failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Scaffold authentication (Breeze) if not already installed.
     */
    protected function scaffoldAuth(): void
    {
        if ($this->option('no-auth')) {
            $this->info('⏭️  Skipping auth scaffolding (--no-auth flag).');
            return;
        }

        // Check if Breeze or Jetstream is already installed
        if (class_exists('Laravel\Breeze\BreezeServiceProvider') || class_exists('Laravel\Jetstream\JetstreamServiceProvider')) {
            $this->info('🔐 Auth scaffolding already installed (Breeze/Jetstream detected).');
            return;
        }

        // Check if laravel/breeze is available in the vendor directory
        $breezeInstalled = class_exists('Laravel\Breeze\BreezeServiceProvider')
            || File::exists(base_path('vendor/laravel/breeze'));

        if (!$breezeInstalled) {
            $this->warn('   ⚠️  Laravel Breeze is not installed. Auth scaffolding skipped.');
            $this->warn('   The library\'s own auth views (login, register) will work independently.');
            $this->warn('   To add Breeze later, run: composer require laravel/breeze --dev && php artisan breeze:install blade');
            return;
        }

        $this->info('🔐 Installing Laravel Breeze (Blade stack)...');

        try {
            Artisan::call('breeze:install', ['stack' => 'blade']);
            $this->info('   ✅ Breeze installed successfully.');
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Breeze install failed: ' . $e->getMessage());
            $this->warn('   You can install manually: php artisan breeze:install blade');
            $this->hasErrors = true;
        }
    }

    /**
     * Create the storage symlink if it doesn't exist.
     */
    protected function createStorageLink(): void
    {
        $this->info('🔗 Checking storage link...');

        if (file_exists(public_path('storage'))) {
            $this->info('   ✅ Storage link already exists.');
            return;
        }

        try {
            Artisan::call('storage:link');
            $this->info('   ✅ Storage link created.');
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Storage link failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate application key if not set.
     */
    protected function generateAppKey(): void
    {
        if (empty(config('app.key'))) {
            $this->info('🔑 Generating application key...');
            Artisan::call('key:generate');
            $this->info('   ✅ Application key generated.');
        }
    }

    /**
     * Clear all caches.
     */
    protected function clearCaches(): void
    {
        $this->info('🧹 Clearing caches...');

        $commands = ['view:clear', 'config:clear', 'cache:clear', 'route:clear'];

        foreach ($commands as $command) {
            try {
                Artisan::call($command);
            } catch (\Exception $e) {
                // Silently continue — cache drivers may not be configured yet
            }
        }

        $this->info('   ✅ Caches cleared.');
    }

    /**
     * Verify that key views resolve, assets are accessible, and the User model
     * is properly configured with required traits.
     */
    protected function verifyInstallation(): void
    {
        $this->info('🔍 Verifying installation...');

        $checks = 0;
        $passed = 0;

        // Check config
        $checks++;
        if (file_exists(config_path('ui-library.php'))) {
            $this->info('   ✅ Config file exists: config/ui-library.php');
            $passed++;
        } else {
            $this->warn('   ⚠️  Config file missing: config/ui-library.php');
        }

        // Check assets
        $checks++;
        $assetPath = public_path('vendor/ui-library/bootstrap/assets/css/soft-ui-dashboard.css');
        if (file_exists($assetPath)) {
            $this->info('   ✅ Core CSS asset accessible.');
            $passed++;
        } else {
            $this->warn('   ⚠️  Core CSS asset missing: vendor/ui-library/bootstrap/assets/css/soft-ui-dashboard.css');
        }

        // Check JS assets
        $checks++;
        $jsPath = public_path('vendor/ui-library/assets/js/quicker-faster.js');
        if (file_exists($jsPath)) {
            $this->info('   ✅ Core JS asset accessible.');
            $passed++;
        } else {
            $this->warn('   ⚠️  Core JS asset missing: vendor/ui-library/assets/js/quicker-faster.js');
        }

        // Check that views namespace resolves
        $checks++;
        try {
            if (view()->exists('qf-core::admin.dashboard')) {
                $this->info('   ✅ Core views namespace resolves (qf-core::admin.dashboard).');
                $passed++;
            } else {
                $this->warn('   ⚠️  Core view qf-core::admin.dashboard does not resolve.');
            }
        } catch (\Exception $e) {
            $this->warn('   ⚠️  View resolution check failed: ' . $e->getMessage());
        }

        // Check Livewire is registered
        $checks++;
        if (class_exists('Livewire\Livewire')) {
            $this->info('   ✅ Livewire is available.');
            $passed++;
        } else {
            $this->warn('   ⚠️  Livewire class not found.');
        }

        // Check user model configuration
        $checks++;
        $userModel = config('ui-library.user.model')
            ?? config('auth.providers.users.model');
        if ($userModel && class_exists($userModel)) {
            $this->info("   ✅ User model configured: {$userModel}");
            $passed++;

            // The class was already loaded earlier in this process, so
            // `class_uses()` would return stale information after we modify the
            // file. Verify the traits from the source file instead.
            $modelFile = (new \ReflectionClass($userModel))->getFileName();
            $className = class_basename($userModel);

            // Check HasUILibraryUser trait
            $checks++;
            if (UserModelTraitInjector::usesTrait(File::get($modelFile), $className, \QuickerFaster\UILibrary\Traits\HasUILibraryUser::class)) {
                $this->info('   ✅ HasUILibraryUser trait present on User model.');
                $passed++;
            } else {
                $this->warn('   ⚠️  HasUILibraryUser trait missing from User model.');
            }

            // Check HasRoles trait
            $checks++;
            if (UserModelTraitInjector::usesTrait(File::get($modelFile), $className, \Spatie\Permission\Traits\HasRoles::class)) {
                $this->info('   ✅ HasRoles trait present on User model.');
                $passed++;
            } else {
                $this->warn('   ⚠️  HasRoles trait missing from User model.');
            }
        } else {
            $this->warn('   ⚠️  User model not configured or class not found.');
        }

        $this->newLine();
        $this->info("   Verification: {$passed}/{$checks} checks passed.");

        if ($passed < $checks) {
            $this->hasErrors = true;
        }
    }

    /**
     * Set a key-value pair in the .env file.
     *
     * Creates the key if it doesn't exist, or updates it if it does.
     */
    protected function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return;
        }

        $contents = File::get($envPath);

        // Escape special regex characters in the value
        $escapedValue = preg_quote($value, '/');

        if (preg_match("/^{$key}=.*/m", $contents)) {
            // Key exists — update it
            $contents = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $contents
            );
        } else {
            // Key doesn't exist — append it
            $contents .= "\n{$key}={$value}\n";
        }

        File::put($envPath, $contents);
    }
}

/**
 * Check if a class uses a given trait (including recursively via parent traits).
 *
 * @param  string|object  $class  Class name or instance
 * @param  string         $trait  Fully-qualified trait name
 * @return bool
 */
if (!function_exists('has_trait')) {
    function has_trait($class, string $trait): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        // Check with PHP's built-in class_uses (does NOT recurse into parent traits)
        $traits = class_uses($class);

        if (in_array($trait, $traits)) {
            return true;
        }

        // Recursively check traits of traits
        foreach ($traits as $usedTrait) {
            if (has_trait_recursive($usedTrait, $trait)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('has_trait_recursive')) {
    function has_trait_recursive(string $class, string $trait): bool
    {
        $traits = class_uses($class);

        if (in_array($trait, $traits)) {
            return true;
        }

        foreach ($traits as $usedTrait) {
            if (has_trait_recursive($usedTrait, $trait)) {
                return true;
            }
        }

        return false;
    }
}