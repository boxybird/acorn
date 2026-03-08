<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPatientLocale
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $patientId = $request->session()->get('patient_id');

        if ($patientId) {
            $patient = Patient::find($patientId);

            if ($patient instanceof Patient) {
                $locale = $patient->preferred_locale ?? $this->detectLocale($request);
                app()->setLocale($locale);

                return $next($request);
            }
        }

        /** @var string|null $sessionLocale */
        $sessionLocale = $request->session()->get('locale');

        $locale = (is_string($sessionLocale) && in_array($sessionLocale, ['en', 'es'], true))
            ? $sessionLocale
            : $this->detectLocale($request);
        app()->setLocale($locale);

        return $next($request);
    }

    private function detectLocale(Request $request): string
    {
        $acceptLanguage = $request->header('Accept-Language', 'en');

        if (str_contains((string) $acceptLanguage, 'es')) {
            return 'es';
        }

        return 'en';
    }
}
