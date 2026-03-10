<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Services\FormSchemaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class IntakePdfController extends Controller
{
    public function __invoke(Intake $intake, FormSchemaService $formSchemaService): Response
    {
        $intake->load(['patient', 'formResponses', 'signatures']);

        $schemas = collect($formSchemaService->all())
            ->sortBy('order')
            ->values()
            ->all();

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('pdf.intake-summary', [
            'intake' => $intake,
            'schemas' => $schemas,
        ]);

        $childName = $intake->child_name ?? 'intake';
        $filename = str($childName)->slug()->append('-intake-summary.pdf')->toString();

        return $pdf->download($filename);
    }
}
