<?php

namespace App\Http\Controllers\Staff;

use App\Enums\IntakeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\FlagFormRequest;
use App\Jobs\SyncIntakeToMonday;
use App\Models\Intake;
use App\Models\IntakeFlag;
use App\Services\FormSchemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntakeController extends Controller
{
    public function index(Request $request): Response
    {
        $builder = Intake::query()
            ->with('patient')
            ->whereNot('status', IntakeStatus::Active)
            ->latest();

        if ($request->filled('status')) {
            $builder->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $builder->whereHas('patient', fn ($q) => $q->whereBlindIndex('email', $search));
        }

        /** @var array<string, int> $statusCounts */
        $statusCounts = [];

        foreach (IntakeStatus::cases() as $status) {
            if ($status === IntakeStatus::Active) {
                continue;
            }

            $statusCounts[$status->value] = Intake::query()
                ->where('status', $status)
                ->count();
        }

        return Inertia::render('staff/IntakeList', [
            'intakes' => $builder->paginate(20),
            'statusCounts' => $statusCounts,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function show(Intake $intake, FormSchemaService $formSchemaService): Response
    {
        if ($intake->status === IntakeStatus::Submitted) {
            $intake->update(['status' => IntakeStatus::UnderReview]);
        }

        $intake->load(['patient', 'formResponses', 'notes.user', 'notes.patient', 'flags.formResponse', 'flags.user']);

        $schemas = collect($formSchemaService->all())
            ->map(function (array $schema): array {
                /** @var string $title */
                $title = $schema['title'];

                return [
                    'key' => $schema['key'],
                    'title' => __($title),
                    'order' => $schema['order'],
                ];
            })
            ->sortBy('order')
            ->values()
            ->all();

        return Inertia::render('staff/IntakeDetail', [
            'intake' => $intake,
            'formResponses' => $intake->formResponses,
            'notes' => $intake->notes->sortBy('created_at')->values(),
            'flags' => $intake->flags,
            'schemas' => $schemas,
        ]);
    }

    public function approve(Intake $intake): RedirectResponse
    {
        $intake->update(['status' => IntakeStatus::Approved]);

        if (config('services.monday.api_token')) {
            SyncIntakeToMonday::dispatch($intake);
        }

        return back();
    }

    public function flag(Intake $intake, FlagFormRequest $flagFormRequest): RedirectResponse
    {
        IntakeFlag::query()->create([
            'intake_id' => $intake->id,
            'form_response_id' => $flagFormRequest->validated('form_response_id'),
            'user_id' => auth()->id(),
            'reason' => $flagFormRequest->validated('reason'),
        ]);

        $intake->update(['status' => IntakeStatus::Flagged]);

        return back();
    }

    public function resolveFlag(Intake $intake, IntakeFlag $intakeFlag): RedirectResponse
    {
        $intakeFlag->update(['resolved_at' => now()]);

        $unresolvedCount = $intake->flags()->whereNull('resolved_at')->count();

        if ($unresolvedCount === 0) {
            $intake->update(['status' => IntakeStatus::UnderReview]);
        }

        return back();
    }
}
