<?php

use App\Http\Controllers\Intake\MagicLinkController;
use App\Http\Middleware\AuthenticatePatient;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('intake')->name('intake.')->group(function (): void {
    Route::get('/', [MagicLinkController::class, 'landing'])->name('landing');
    Route::post('/request-link', [MagicLinkController::class, 'requestLink'])
        ->middleware('throttle:3,1')
        ->name('request-link');
    Route::get('/verify/{token}', [MagicLinkController::class, 'verify'])->name('verify');

    Route::middleware(AuthenticatePatient::class)->group(function (): void {
        Route::get('/dashboard', fn () => Inertia::render('intake/Dashboard'))->name('dashboard');
    });
});
