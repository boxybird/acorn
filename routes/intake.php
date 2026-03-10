<?php

use App\Http\Controllers\Intake\DashboardController;
use App\Http\Controllers\Intake\DocumentController;
use App\Http\Controllers\Intake\FormCompleteController;
use App\Http\Controllers\Intake\FormController;
use App\Http\Controllers\Intake\IntakeSelectorController;
use App\Http\Controllers\Intake\MagicLinkController;
use App\Http\Controllers\Intake\SignatureController;
use App\Http\Middleware\AuthenticatePatient;
use App\Http\Middleware\SetPatientLocale;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('intake')->name('intake.')->group(function (): void {
    Route::get('/', [MagicLinkController::class, 'landing'])
        ->middleware(SetPatientLocale::class)
        ->name('landing');
    Route::post('/set-locale-guest', function (Request $request): RedirectResponse {
        $request->validate(['locale' => ['required', 'string', 'in:en,es']]);
        $request->session()->put('locale', $request->input('locale'));

        return back();
    })->name('set-locale-guest');
    Route::post('/request-link', [MagicLinkController::class, 'requestLink'])
        ->middleware([SetPatientLocale::class, 'throttle:3,1'])
        ->name('request-link');
    Route::get('/verify/{token}', [MagicLinkController::class, 'verify'])
        ->middleware(SetPatientLocale::class)
        ->name('verify');

    Route::middleware([AuthenticatePatient::class, SetPatientLocale::class])->group(function (): void {
        Route::post('/select/new', [IntakeSelectorController::class, 'create'])->name('select.new');
        Route::post('/select/{intake}', [IntakeSelectorController::class, 'choose'])->name('select.choose');
        Route::post('/set-locale', function (Request $request): RedirectResponse {
            $request->validate(['locale' => ['required', 'string', 'in:en,es']]);

            /** @var int $patientId */
            $patientId = $request->session()->get('patient_id');

            Patient::query()->where('id', $patientId)->update([
                'preferred_locale' => $request->input('locale'),
            ]);

            return back();
        })->name('set-locale');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/form/{schemaKey}', [FormController::class, 'show'])->name('form.show');
        Route::put('/form/{schemaKey}', [FormController::class, 'save'])->name('form.save');
        Route::post('/form/{schemaKey}/complete', [FormController::class, 'complete'])->name('form.complete');
        Route::get('/form/{schemaKey}/completed', [FormCompleteController::class, 'show'])->name('form.completed');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('/signatures', [SignatureController::class, 'store'])->name('signatures.store');
    });
});
