<?php

namespace App\Http\Controllers\Intake;

use App\Actions\GenerateMagicLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\Intake\RequestMagicLinkRequest;
use App\Models\Intake;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MagicLinkController extends Controller
{
    public function landing(): Response
    {
        return Inertia::render('intake/Landing');
    }

    public function requestLink(RequestMagicLinkRequest $requestMagicLinkRequest, GenerateMagicLink $generateMagicLink): RedirectResponse
    {
        /** @var string $email */
        $email = $requestMagicLinkRequest->validated('email');

        $generateMagicLink->handleForEmail($email);

        return back()->with('status', __('intake.magic_link_sent'));
    }

    public function verify(string $token, Request $request): RedirectResponse
    {
        $patient = Patient::query()
            ->where('magic_link_token', $token)
            ->first();

        if (! $patient || ! $patient->hasValidMagicLink()) {
            return redirect()->route('intake.landing')
                ->with('error', __('intake.link_expired'));
        }

        $patient->update([
            'magic_link_token' => null,
            'magic_link_expires_at' => null,
        ]);

        /** @var string|null $sessionLocale */
        $sessionLocale = $request->session()->get('locale');

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('patient_id', $patient->id);

        if ($sessionLocale) {
            $request->session()->put('locale', $sessionLocale);

            if ($patient->preferred_locale === null) {
                $patient->update(['preferred_locale' => $sessionLocale]);
            }
        }

        $intake = Intake::query()
            ->where('patient_id', $patient->id)
            ->oldest()
            ->firstOrCreate(['patient_id' => $patient->id]);

        $request->session()->put('intake_id', $intake->id);

        return redirect()->route('intake.dashboard');
    }
}
