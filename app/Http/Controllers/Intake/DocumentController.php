<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\FormResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'form_response_id' => ['required', 'exists:form_responses,id'],
            'field_key' => ['required', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        /** @var int $intakeId */
        $intakeId = $request->session()->get('intake_id');

        /** @var int $formResponseId */
        $formResponseId = $request->input('form_response_id');

        $formResponse = FormResponse::query()
            ->where('id', $formResponseId)
            ->where('intake_id', $intakeId)
            ->firstOrFail();

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        /** @var string $path */
        $path = $file->store('documents/'.$intakeId, 'local');

        $document = Document::query()->create([
            'intake_id' => $intakeId,
            'form_response_id' => $formResponse->id,
            'field_key' => $request->input('field_key'),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json(['id' => $document->id], 201);
    }

    public function destroy(Document $document, Request $request): JsonResponse
    {
        /** @var int $intakeId */
        $intakeId = $request->session()->get('intake_id');

        if ($document->intake_id !== $intakeId) {
            abort(403);
        }

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return response()->json(['status' => 'deleted']);
    }
}
