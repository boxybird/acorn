<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Http\Requests\Intake\RequestMagicLinkRequest;
use App\Models\Patient;
use App\Services\MagicLinkService;
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

    public function requestLink(RequestMagicLinkRequest $requestMagicLinkRequest, MagicLinkService $magicLinkService): RedirectResponse
    {
        /** @var string $email */
        $email = $requestMagicLinkRequest->validated('email');

        $magicLinkService->sendToEmail($email);

        return back()->with('status', 'Check your email for a magic link.');
    }

    public function verify(string $token, Request $request): RedirectResponse
    {
        $patient = Patient::query()
            ->where('magic_link_token', $token)
            ->first();

        if (! $patient || ! $patient->hasValidMagicLink()) {
            return redirect()->route('intake.landing')
                ->with('error', 'This link is invalid or has expired.');
        }

        $patient->update([
            'magic_link_token' => null,
            'magic_link_expires_at' => null,
        ]);

        $request->session()->put('patient_id', $patient->id);

        return redirect()->route('intake.dashboard');
    }
}
