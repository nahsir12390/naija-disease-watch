<?php

use App\Http\Controllers\DatasetController;
use App\Http\Controllers\SurveillanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', SurveillanceController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('can:manage-datasets')->group(function () {
        Route::get('datasets', [DatasetController::class, 'index'])->name('datasets.index');
        Route::post('datasets', [DatasetController::class, 'store'])->middleware('throttle:10,1')->name('datasets.store');
        Route::get('datasets/template', [DatasetController::class, 'template'])->name('datasets.template');
    });
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
