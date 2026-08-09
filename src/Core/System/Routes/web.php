<?php

use Illuminate\Support\Facades\Route;

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
        $viewName = "{$module}::{$view}";

        if (view()->exists($viewName)) {
            return view($viewName, ['id' => $id]);
        }

        // Fallback: try qf-core namespace
        $coreViewName = "qf-core::{$module}.{$view}";
        if (view()->exists($coreViewName)) {
            return view($coreViewName, ['id' => $id]);
        }

        abort(404, "View [{$viewName}] not found.");
    })->where('module', '[a-z-]+')->where('view', '[a-z-]+')->where('id', '[0-9]+');
});
