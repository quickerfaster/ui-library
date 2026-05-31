<?php

use Illuminate\Support\Facades\Route;



Route::group([
    'prefix' => 'access',
], function () {
    Route::get('access-control-management/{module}', function () {

        // Chech if only admin can access this view. If the user is not admin do not proceed
        if (!auth()->check() || ( auth()->user() && !auth()->user()->hasAnyRole(\App\Modules\Admin\Services\AuthorizationService::ADMIN_ROLES))) {
            abort(403, 'Unauthorized');
        }


        return view('admin::access-control-management', [
            'selectedModule' => request("module"),
            'isUrlAccess' => true,
        ]);
    });
})->middleware(['web', 'auth']);



















// Routes for ActivityLog

// Create Route
Route::get('activity-logs/create', function (\Illuminate\Http\Request $request) {
    return view('admin::activity-logs.create', [
        'configKey' => 'admin.activity_log',
        'returnParams' => $request->only(['page', 'perPage', 'search', 'sort', 'activeFilters'])
    ]);
})->name('activity-logs.create');

// Show Route
Route::get('activity-logs/{id}', function (\Illuminate\Http\Request $request, $id) {
    return view('admin::activity-logs.show', [
        'recordId' => (int) $id,
        'configKey' => 'admin.activity_log',
        'returnParams' => $request->only(['page', 'perPage', 'search', 'sort', 'activeFilters'])
    ]);
})->name('activity-logs.show')->where('id', '[0-9]+'); 

// Edit Route
Route::get('activity-logs/{id}/edit', function (\Illuminate\Http\Request $request, $id) {
    return view('admin::activity-logs.edit', [
        'recordId' => (int) $id,
        'configKey' => 'admin.activity_log',
        'returnParams' => $request->only(['page', 'perPage', 'search', 'sort', 'activeFilters'])
    ]);
})->name('activity-logs.edit')->where('id', '[0-9]+'); // And here;


// Routes for Location

// Create Route
Route::get('locations/create', function (\Illuminate\Http\Request $request) {
    return view('admin::locations.create', [
        'configKey' => 'admin.location',
        'returnParams' => $request->only(['page', 'perPage', 'search', 'sort', 'activeFilters'])
    ]);
})->name('locations.create');

// Show Route
Route::get('locations/{id}', function (\Illuminate\Http\Request $request, $id) {
    return view('admin::locations.show', [
        'recordId' => (int) $id,
        'configKey' => 'admin.location',
        'returnParams' => $request->only(['page', 'perPage', 'search', 'sort', 'activeFilters'])
    ]);
})->name('locations.show')->where('id', '[0-9]+'); 

// Edit Route
Route::get('locations/{id}/edit', function (\Illuminate\Http\Request $request, $id) {
    return view('admin::locations.edit', [
        'recordId' => (int) $id,
        'configKey' => 'admin.location',
        'returnParams' => $request->only(['page', 'perPage', 'search', 'sort', 'activeFilters'])
    ]);
})->name('locations.edit')->where('id', '[0-9]+'); // And here;
