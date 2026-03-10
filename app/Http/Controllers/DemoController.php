<?php

namespace App\Http\Controllers;

use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DemoController extends Controller
{
    public function loginAsPatient(Request $request, Patient $patient): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->put('patient_id', $patient->id);

        $intake = Intake::query()
            ->where('patient_id', $patient->id)
            ->oldest()
            ->firstOrCreate(['patient_id' => $patient->id]);

        $request->session()->put('intake_id', $intake->id);

        return redirect()->route('intake.dashboard');
    }

    public function loginAsUser(Request $request, User $user): RedirectResponse
    {
        $request->session()->forget('patient_id');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function resetData(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Schema::withoutForeignKeyConstraints(function (): void {
            foreach (['intake_flags', 'intake_notes', 'signatures', 'documents', 'form_responses', 'intakes', 'patients', 'users', 'sessions'] as $table) {
                if (Schema::hasTable($table)) {
                    \Illuminate\Support\Facades\DB::table($table)->truncate();
                }
            }
        });

        $databaseSeeder = new \Database\Seeders\DatabaseSeeder;
        $databaseSeeder->run();

        return redirect()->route('home');
    }
}
