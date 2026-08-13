<?php

namespace QuickerFaster\UILibrary\Providers;

use Illuminate\Support\ServiceProvider;
use QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver;
use QuickerFaster\UILibrary\Services\Navigation\NullWorkspaceResolver;

class WorkspaceServiceProvider extends ServiceProvider
{
    /**
     * Register workspace-related bindings.
     *
     * The default binding (NullWorkspaceResolver) returns an empty context
     * so that no workspace filtering is applied — fully backward compatible.
     *
     * Consuming applications override this by binding their own implementation
     * in their AppServiceProvider or a dedicated service provider:
     *
     *   $this->app->singleton(
     *       \QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver::class,
     *       \App\Services\WorkspaceResolver::class
     *   );
     */
    public function register(): void
    {
        $this->app->singleton(WorkspaceResolver::class, NullWorkspaceResolver::class);
    }
}