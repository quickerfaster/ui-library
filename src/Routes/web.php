<?php

use Illuminate\Support\Facades\Route;
use QuickerFaster\UILibrary\Http\Controllers\Exports\ExportController;
use QuickerFaster\UILibrary\Http\Controllers\Prints\GenericTablePrintController;
use QuickerFaster\UILibrary\Http\Controllers\SocialiteController;
use QuickerFaster\UILibrary\Http\Livewire\Wizards\SetupWizard;
use Illuminate\Http\Request;
use QuickerFaster\UILibrary\Http\Controllers\Prints\GenericDetailPagePrintController;
use QuickerFaster\UILibrary\Http\Controllers\Imports\ImportController;




/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});







Route::group(['middleware' => 'web'], function () {
    Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
        ->name('socialite.redirect')
        ->where('provider', 'google|github'); // adjust as needed


    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
        ->name('socialite.callback')
        ->where('provider', 'google|github');

    // for now let default to he main dashboard
    Route::get('/home', function () {
        // return view('qf::home');
        return view('hr::dashboard');
    });

    Route::get('/export/data', [ExportController::class, 'export'])->name('export.data');
    Route::get('/export/all', [ExportController::class, 'exportAll'])->name('export.all');
    Route::post('/export/queue', [ExportController::class, 'queueExport'])->name('export.queue');
    Route::get('/export/status/{id}', [ExportController::class, 'exportStatus'])->name('export.status');
    Route::get('/export/download/{token}', [ExportController::class, 'download'])->name('export.download');
    Route::post('/export/cancel/{id}', [ExportController::class, 'cancelExport'])->name('export.cancel');
    Route::get('/export/template/{configKey}', [ExportController::class, 'exportTemplate'])->name('export.template');

    Route::get('/import/download-errors/{import}', [ImportController::class, 'downloadErrors'])->name('import.download-errors');
    Route::get('/import/status/{id}', [ImportController::class, 'status'])->name('import.status');

    Route::get('/print/data', [GenericTablePrintController::class, 'print'])->name('print.data');



    Route::get('/setup', function () {
        return view('qf::setup');
    })->name('setup.wizard');



    Route::post('/user/complete-tour', function (Request $request) {
        $request->user()->update(['has_seen_tour' => true]);
        \Log::info($request->user());
        return response()->json(['success' => true]);
    })->middleware('auth')->name('tour.complete');

    Route::get('/user/restart-tour', function (Request $request) {
        $request->user()->update(['has_seen_tour' => false]);
        return redirect()->to('/hr/dashboard'); // Redirect to where the tour lives
    })->middleware('auth')->name('tour.restart');





    Route::get('/print/{configKey}/{id}', [GenericDetailPagePrintController::class, 'show'])
        ->name('generic.print');


    Route::middleware(['auth'])->group(function () {
        Route::get('/my-profile', [\App\Modules\Hr\Http\Controllers\EmployeeProfileController::class, 'show'])
            ->name('hr.employee.profile');

        Route::get('/my-preferences', function () {
            return view('hr::my-preferences');
        })->name('my-preferences');
    });






    Route::get('/documents/{document}/download', 
        [\QuickerFaster\UILibrary\Http\Controllers\Documents\DocumentController::class, 'download'])
        ->name('documents.download');











    Route::get('/test-components', function () {
        return view('testing');
    });


});



