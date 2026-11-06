<?php

use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\SourceReportController;
use App\Http\Controllers\SurveillanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', SurveillanceController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('can:manage-datasets')->group(function () {
        Route::get('datasets', [DatasetController::class, 'index'])->name('datasets.index');
        Route::post('datasets', [DatasetController::class, 'store'])->middleware('throttle:10,1')->name('datasets.store');
        Route::get('datasets/template', [DatasetController::class, 'template'])->name('datasets.template');
        Route::get('datasets/create', [DatasetController::class, 'create'])->name('datasets.create');
        Route::post('records', [DatasetController::class, 'save'])->name('records.store');
        Route::get('records/{diseaseReport}/edit', [DatasetController::class, 'edit'])->name('records.edit');
        Route::put('records/{diseaseReport}', [DatasetController::class, 'update'])->name('records.update');
        Route::delete('records/{diseaseReport}', [DatasetController::class, 'destroy'])->name('records.destroy');
        Route::get('sources', [SourceReportController::class, 'index'])->name('sources.index');
        Route::post('sources/sync', [SourceReportController::class, 'sync'])->middleware('throttle:3,1')->name('sources.sync');
        Route::get('sources/{sourceReport}', [SourceReportController::class, 'show'])->name('sources.show');
        Route::post('sources/{sourceReport}/extract', [SourceReportController::class, 'extract'])->middleware('throttle:5,1')->name('sources.extract');
        Route::post('sources/{sourceReport}/publish', [SourceReportController::class, 'publish'])->name('sources.publish');
        Route::post('sources/{sourceReport}/dismiss', [SourceReportController::class, 'dismiss'])->name('sources.dismiss');
        Route::get('administration', [AdministrationController::class, 'index'])->name('administration.index');
        Route::put('administration/users/{user}', [AdministrationController::class, 'role'])->name('administration.role');
        Route::put('administration/settings', [AdministrationController::class, 'settings'])->name('administration.settings');
    });
    Route::post('administration/claim', [AdministrationController::class, 'claim'])->middleware('throttle:3,1')->name('administration.claim');
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
