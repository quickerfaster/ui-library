<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('qf-core::admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/admin/users', function () {
        return view('qf-core::admin.users');
    })->name('admin.users');

    Route::get('/admin/roles', function () {
        return view('qf-core::admin.roles');
    })->name('admin.roles');

    Route::get('/admin/permissions', function () {
        return view('qf-core::admin.permissions');
    })->name('admin.permissions');
});
