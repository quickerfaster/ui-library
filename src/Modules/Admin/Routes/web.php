<?php

use Illuminate\Support\Facades\Route;



Route::group([
    'prefix' => 'access',
], function () {
    Route::get('access-control-management/{module}', function () {

        // Chech if only admin can access this view. If the user is not admin do not proceed
        if (!auth()->check() || !auth()->user()->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Unauthorized');
        }


        return view('admin::access-control-management', [
            'selectedModule' => request("module"),
            'isUrlAccess' => true,
        ]);
    });
});
















