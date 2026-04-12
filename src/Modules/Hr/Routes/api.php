<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Http\Controllers\AuthController;

use App\Modules\Hr\Http\Controllers\ClockEventController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;




/*Route::post('/hr/test', function () {
    //return $controller->store($request);
    dd('api route test');
});


Route::middleware([
    'auth:sanctum',
    // InitializeTenancyByDomain::class,
    // PreventAccessFromCentralDomains::class,
])->group(function () {

Route::post('hr/attendance/store', [ClockEventController::class, 'store'])
    ->name('hr.attendance.store');

});*/











Route::group([
    'prefix' => 'hr',
    'middleware' => 'auth:sanctum',
], function () {

    Route::post('attendance/store', [ClockEventController::class, 'store'])->name('hr.attendance.store');
    Route::post('attendance/batch-store', [\App\Modules\Hr\Http\Controllers\ClockEventController::class, 'batchStore']);
    Route::get('employee/sync', [\App\Modules\Hr\Http\Controllers\UserSyncController::class, 'syncUser']);
    Route::get('employee/sync-all', [\App\Modules\Hr\Http\Controllers\UserSyncController::class, 'syncAllEmployees']);
    Route::get('employee/sync-all-with-profiles', [\App\Modules\Hr\Http\Controllers\UserSyncController::class, 'syncAllEmployeesWithProfiles']);
    Route::get('employee/sync-all-with-users-and-profiles', [\App\Modules\Hr\Http\Controllers\UserSyncController::class, 'syncAllEmployeesWithUserAndProfiles']);

    
    



});


/*
  [{"device_id":"c213a4332a9f801a","device_name":"INFINIX Infinix X6835B","employee_id":"1","employee_number":"EMP-2025-001","event_type":"check-in","latitude":9.1025352,"location_name":"Jahi, Federal Capital Territory, Nigeria","longitude":7.4430279,"notes":"","timestamp":1766764808298,"timezone":"Africa/Lagos"},{"device_id":"c213a4332a9f801a","device_name":"INFINIX Infinix X6835B","employee_id":"1","employee_number":"EMP-2025-001","event_type":"check-out","latitude":9.1025352,"location_name":"Jahi, Federal Capital Territory, Nigeria","longitude":7.4430279,"notes":"","timestamp":1766764816393,"timezone":"Africa/Lagos"}]
"message": "The route api/hr/attendance/store-batch could not be found.",

{
  "employee_id": "1",           // Actual employee ID
  "employee_number": "EMP-2025-001", // Employee number
  "event_type": "check-in",
  "timestamp": 1766764808298,
  "device_id": "...",
  "device_name": "...",
  "location_name": "...",
  "timezone": "...",
  "notes": "...",
  "latitude": 9.1025352,
  "longitude": 7.4430279
}

*/
                                                                                                       
