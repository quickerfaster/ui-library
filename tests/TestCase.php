<?php

namespace QuickerFaster\UILibrary\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\TestCase as Orchestra;
use QuickerFaster\UILibrary\Providers\UILibraryServiceProvider;

abstract class TestCase extends Orchestra
{
    use WithLaravelMigrations;
    use RefreshDatabase;

    /**
     * Prevent auto-discovery of the library's own providers so that
     * FortifyServiceProvider (which references App\Actions\Fortify\*)
     * and ModuleServiceProvider (which scans app/Modules) do not boot
     * in the isolated Testbench environment.
     */
    public function ignorePackageDiscoveriesFrom(): array
    {
        return ['quicker-faster/ui-library'];
    }

    protected function getPackageProviders($app): array
    {
        return [
            UILibraryServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Point the library at a User model that exists in the test
        // environment (the default \App\Models\User does not).
        $app['config']->set('ui-library.user.model', \QuickerFaster\UILibrary\Tests\Fixtures\User::class);

        // Auth provider must match so Auth::login() resolves correctly.
        $app['config']->set('auth.providers.users.model', \QuickerFaster\UILibrary\Tests\Fixtures\User::class);

        // Approval bypass roles — keep the default for guard tests.
        $app['config']->set('ui-library.approvals.bypass_roles', ['super_admin']);

        // Disable async notifications so dispatch() is called synchronously.
        $app['config']->set('ui-library.notifications.queue', false);

        // Ensure a default notification channel exists so NotificationService
        // can be resolved without errors.
        $app['config']->set('ui-library.notifications.channels', [
            'database' => \QuickerFaster\UILibrary\Services\Notifications\Channels\DatabaseChannel::class,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Default Laravel migrations (users, password_resets, etc.) are
        // loaded by the WithLaravelMigrations trait. Load the library's
        // own migrations on top.
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');

        // Spatie permission tables are required by Livewire components
        // (e.g. the workflow definition wizard) that query roles during
        // rendering. The library depends on spatie/laravel-permission but
        // its migrations are published as .stub files, so we provide a
        // test-only migration.
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
    }
}