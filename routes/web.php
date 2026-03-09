<?php

use App\Http\Controllers\Staff\IntakeController;
use App\Http\Controllers\Staff\PatientController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::prefix('staff')->name('staff.')->group(function (): void {
        Route::get('/intakes', [IntakeController::class, 'index'])->name('intakes.index');
        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/intake.php';
