<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Models\Signature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignatureController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'form_response_id' => ['required', 'exists:form_responses,id'],
            'field_key' => ['required', 'string'],
            'signature' => ['required', 'string'],
        ]);

        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        /** @var int $formResponseId */
        $formResponseId = $request->input('form_response_id');

        $formResponse = FormResponse::query()
            ->where('id', $formResponseId)
            ->where('patient_id', $patientId)
            ->firstOrFail();

        /** @var string $signatureData */
        $signatureData = $request->input('signature');

        $imageData = base64_decode(
            (string) preg_replace('/^data:image\/\w+;base64,/', '', $signatureData),
            true,
        );

        if ($imageData === false) {
            return response()->json(['error' => 'Invalid signature data'], 422);
        }

        $filename = 'signatures/'.$patientId.'/'.Str::uuid().'.png';
        Storage::disk('local')->put($filename, $imageData);

        $signature = Signature::query()->create([
            'patient_id' => $patientId,
            'form_response_id' => $formResponse->id,
            'field_key' => $request->input('field_key'),
            'image_path' => $filename,
        ]);

        return response()->json(['id' => $signature->id], 201);
    }
}
