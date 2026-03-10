<?php

use App\Http\Controllers\Staff\IntakeController;
use App\Http\Controllers\Staff\IntakePdfController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', fn () => \Inertia\Inertia::render('Welcome', [
    'canRegister' => ! config('demo.enabled') && Features::enabled(Features::registration()),
]))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::redirect('dashboard', '/staff/intakes')->name('dashboard');

    Route::prefix('staff')->name('staff.')->group(function (): void {
        Route::get('/intakes', [IntakeController::class, 'index'])->name('intakes.index');
        Route::get('/intakes/{intake}', [IntakeController::class, 'show'])->name('intakes.show');
        Route::post('/intakes/{intake}/approve', [IntakeController::class, 'approve'])->name('intakes.approve');
        Route::post('/intakes/{intake}/flag', [IntakeController::class, 'flag'])->name('intakes.flag');
        Route::post('/intakes/{intake}/flags/{intakeFlag}/resolve', [IntakeController::class, 'resolveFlag'])->name('intakes.flags.resolve');
        Route::get('/intakes/{intake}/pdf', IntakePdfController::class)->name('intakes.pdf');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/intake.php';
