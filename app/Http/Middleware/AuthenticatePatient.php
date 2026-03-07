<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePatient
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $patientId = $request->session()->get('patient_id');

        if (! $patientId || ! Patient::find($patientId)) {
            return redirect()->route('intake.landing');
        }

        return $next($request);
    }
}
