<?php

use App\Http\Controllers\DemoController;
use App\Http\Middleware\DemoMode;
use Illuminate\Support\Facades\Route;

Route::prefix('demo')->middleware(['web', DemoMode::class])->group(function (): void {
    Route::post('/login/patient/{patient}', [DemoController::class, 'loginAsPatient'])->name('demo.login.patient');
    Route::post('/login/user/{user}', [DemoController::class, 'loginAsUser'])->name('demo.login.user');
    Route::post('/logout', [DemoController::class, 'logout'])->name('demo.logout');
});
