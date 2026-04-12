<?php

use Illuminate\Support\Facades\Route;
use QuickerFaster\UILibrary\Http\Controllers\Exports\ExportController;
use QuickerFaster\UILibrary\Http\Controllers\Prints\PrintController;
use QuickerFaster\UILibrary\Http\Controllers\SocialiteController;
use QuickerFaster\UILibrary\Http\Livewire\Wizards\SetupWizard;
use Illuminate\Http\Request;




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
    Route::get('/print/data', [PrintController::class, 'print'])->name('print.data');

    Route::post('/export/queue', [ExportController::class, 'queueExport'])->name('export.queue');
    Route::get('/export/status/{id}', [ExportController::class, 'exportStatus'])->name('export.status');
    Route::get('/export/download/{id}', [ExportController::class, 'download'])->name('export.download');



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



    Route::get('/test-components', function () {
        return view('testing');
    });


});



