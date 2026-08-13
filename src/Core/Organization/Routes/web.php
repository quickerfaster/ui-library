<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/organization/dashboard', function () {
        return view('qf-core::organization.dashboard');
    })->name('organization.dashboard');

    Route::get('/organization/companies', function () {
        return view('qf-core::organization.companies');
    })->name('organization.companies');

    Route::get('/organization/branches', function () {
        return view('qf-core::organization.branches');
    })->name('organization.branches');

    Route::get('/organization/departments', function () {
        return view('qf-core::organization.departments');
    })->name('organization.departments');

    Route::get('/organization/divisions', function () {
        return view('qf-core::organization.divisions');
    })->name('organization.divisions');

    Route::get('/organization/business-units', function () {
        return view('qf-core::organization.business-units');
    })->name('organization.business-units');

    Route::get('/organization/locations', function () {
        return view('qf-core::organization.locations');
    })->name('organization.locations');

    Route::get('/organization/teams', function () {
        return view('qf-core::organization.teams');
    })->name('organization.teams');
});