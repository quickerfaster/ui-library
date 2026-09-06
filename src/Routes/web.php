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

    // Home / Dashboard — polished welcome page from the library
    Route::middleware(['auth'])->get('/home', function () {
        return view(config('ui-library.home_view', 'qf::home'));
    })->name('home');

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
        return redirect()->route(config('ui-library.home_route', 'admin.dashboard'));
    })->middleware('auth')->name('tour.restart');





    Route::middleware(['auth'])->group(function () {
        Route::get('/print/{configKey}/{id}', [GenericDetailPagePrintController::class, 'show'])
            ->name('generic.print');
    });

    // Phase 4.4: Organization/Company switching
    Route::middleware(['auth'])->group(function () {
        Route::post('/switch-company/{company}',
            [\QuickerFaster\UILibrary\Http\Controllers\OrganizationSwitchController::class, '__invoke'])
            ->name('company.switch')
            ->where('company', '[0-9]+');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/my-profile', function () {
            return redirect()->route(config('ui-library.home_route', 'admin.dashboard'));
        })->name('profile');

        Route::get('/my-preferences', function () {
            return view('qf::my-preferences');
        })->name('my-preferences');

        Route::get('/my-account', function () {
            return view('qf::my-account');
        })->name('my-account');

        Route::get('/notifications', function () {
            return view('qf::notifications');
        })->name('notifications.index');
    });








    Route::get('/documents/{document}/download', 
        [\QuickerFaster\UILibrary\Http\Controllers\Documents\DocumentController::class, 'download'])
        ->name('documents.download');












    Route::get('/test-components', function () {
        return view('testing');
    });


});
