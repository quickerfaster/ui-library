<?php

use Illuminate\Support\Facades\Route;
use QuickerFaster\UILibrary\Http\Livewire\AccessControls\AccessControlManager;
use Illuminate\Support\Facades\Validator;




// Central reports hub
Route::get('/reports', function () {
    return view('qf::reports.index'); // or a dedicated view
})->name('reports.index');



// System report viewer
Route::get('/report/{reportKey}', function ($reportKey) {
    return view('qf::reports.viewer', ['reportKey' => $reportKey]);
})->name('report.viewer');

// User saved report viewer
Route::get('/report/saved/{reportId}', function ($reportId) {
    return view('qf::reports.viewer-saved', [
        'reportId' => $reportId,
    ]);
})->name('report.viewer.user');


Route::get('/report-builder/{configKey}', function ($configKey) {
    return view('qf::reports.builder', ['configKey' => $configKey]);
})->name('report.builder');



Route::get('/report-builder/{configKey}/{reportId?}', function ($configKey, $reportId = null) {
    return view('qf::reports.builder', ['configKey' => $configKey, 'reportId' => $reportId]);
})->name('report.builder');








Route::get('/{module}/{view}/{id?}', function ($module, $view, $id = null) {
    // Validation

    Validator::make(['module' => $module, 'view' => $view, 'id' => $id], [
        'module' => 'required|string',
        'view' => 'required|string',
        'id' => 'nullable|integer',
    ])->validate();

    $allowedModules = ['system', 'admin',  'hr'];

    if (!in_array($module, $allowedModules)) {
        abort(404, 'Invalid module');
    }


    // Chech if only admin can access this view. If the user is not admin do not proceed
    if (in_array($view, AccessControlManager::ROLE_ADMIN_ONLY_VIEWS)) {
        // Check if the user has the role
        if (!auth()->check() || !auth()->user()->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Unauthorized');
        }
    // If user is  not admin, check if the user has the permission
    } else if (auth()->check() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
        // Build a dynamic permission name
        $permission = "view_".AccessControlManager::getViewPerminsionModelName(($view));

        // Check permission or role
        if (!auth()->user()->can($permission) && $view !=="dashboard" && $view !=="my-profile") {
                abort(403, 'Unauthorized');
        }

    }



    // Compose view path
    $viewName = $module . '::' . $view;



    // Check view existence
    if (view()->exists($viewName)) {
        return view($viewName, ["id" => $id]);
    }

    abort(404, 'View not found');
})->middleware(['web', 'auth']);















