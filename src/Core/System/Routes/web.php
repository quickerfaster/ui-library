<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/system/dashboard', function () {
        return view('qf-core::system.dashboard');
    })->name('system.dashboard');

    Route::get('/system/settings', function () {
        return view('qf-core::system.settings');
    })->name('system.settings');
});

// Catch-all route for business modules (loaded LAST by ModuleServiceProvider)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/{module}/{view}/{id?}', function ($module, $view, $id = null) {
        // -----------------------------------------------------------------
        // 1. Module allow-list check (config-driven)
        // -----------------------------------------------------------------
        $allowedModules = config('ui-library.catch_all.allowed_modules', []);
        if (!in_array($module, $allowedModules, true)) {
            abort(404);
        }

        // -----------------------------------------------------------------
        // 2. Directory-traversal sanitization (defense in depth)
        //    The regex constraint [a-z-]+ already rejects most traversal
        //    sequences, but we explicitly check for null bytes, slashes,
        //    backslashes, and parent-directory tokens as a second layer.
        // -----------------------------------------------------------------
        foreach ([$module, $view] as $segment) {
            if (
                str_contains($segment, "\0")
                || str_contains($segment, '\\')
                || str_contains($segment, '/')
                || str_contains($segment, '..')
                || str_starts_with($segment, '.')
            ) {
                abort(400);
            }
        }

        // -----------------------------------------------------------------
        // 3. Per-view authorization (config-driven)
        //    Consuming apps can set a callable or a Gate ability name.
        // -----------------------------------------------------------------
        $user = auth()->user();

        if (config('ui-library.catch_all.require_auth', true) && !$user) {
            abort(401);
        }

        $authCallback = config('ui-library.catch_all.authorization_callback');
        if ($authCallback && is_callable($authCallback)) {
            if (!$authCallback($user, $module, $view, $id)) {
                abort(403);
            }
        } else {
            $gate = config('ui-library.catch_all.gate');
            if ($gate && !Gate::allows($gate, [$module, $view, $id])) {
                abort(403);
            }
        }

        // -----------------------------------------------------------------
        // 4. View resolution (unchanged logic)
        // -----------------------------------------------------------------
        $viewName = "{$module}::{$view}";

        if (view()->exists($viewName)) {
            return view($viewName, ['id' => $id]);
        }

        // Fallback: try qf-core namespace (with hyphens)
        $coreViewName = "qf-core::{$module}.{$view}";
        if (view()->exists($coreViewName)) {
            return view($coreViewName, ['id' => $id]);
        }

        // Fallback: try with underscores instead of hyphens
        $underscoreView = str_replace('-', '_', $view);
        $coreViewNameUnderscore = "qf-core::{$module}.{$underscoreView}";
        if (view()->exists($coreViewNameUnderscore)) {
            return view($coreViewNameUnderscore, ['id' => $id]);
        }

        abort(404, "View [{$viewName}] not found.");
    })
    ->where('module', '[a-z-]+')
    ->where('view', '[a-z-]+')
    ->where('id', '[0-9]+')
    ->middleware(config('ui-library.catch_all.rate_limiting.enabled', true) ? 'throttle:qf-catch-all' : []);
});
