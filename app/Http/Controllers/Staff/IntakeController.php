<?php

namespace App\Http\Controllers\Staff;

use App\Actions\ApproveIntake;
use App\Actions\FlagFormResponse;
use App\Actions\ResolveIntakeFlag;
use App\Enums\IntakeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\FlagFormRequest;
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
            ->latest();

        if ($request->filled('status')) {
            $builder->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $builder->whereHas('patient', fn ($q) => $q->whereBlindIndex('email', $search));
        }

        /** @var array<string, int> $rawCounts */
        $rawCounts = Intake::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        /** @var array<string, int> $statusCounts */
        $statusCounts = [];

        foreach (IntakeStatus::cases() as $status) {
            $statusCounts[$status->value] = (int) ($rawCounts[$status->value] ?? 0);
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

        $intake->load(['patient', 'formResponses', 'flags.formResponse', 'flags.user']);

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
            'flags' => $intake->flags,
            'schemas' => $schemas,
        ]);
    }

    public function approve(Intake $intake, ApproveIntake $approveIntake): RedirectResponse
    {
        abort_if($intake->status === IntakeStatus::Active, 422, 'Cannot approve an intake that is still in progress.');

        $approveIntake->handle($intake);

        return back();
    }

    public function flag(Intake $intake, FlagFormRequest $flagFormRequest, FlagFormResponse $flagFormResponse): RedirectResponse
    {
        abort_if($intake->status === IntakeStatus::Active, 422, 'Cannot flag an intake that is still in progress.');

        /** @var array{form_response_id: int, reason: string} $validated */
        $validated = $flagFormRequest->validated();

        $flagFormResponse->handle(
            intake: $intake,
            formResponseId: $validated['form_response_id'],
            userId: (int) auth()->id(),
            reason: $validated['reason'],
        );

        return back();
    }

    public function resolveFlag(Intake $intake, IntakeFlag $intakeFlag, ResolveIntakeFlag $resolveIntakeFlag): RedirectResponse
    {
        $resolveIntakeFlag->handle($intake, $intakeFlag);

        return back();
    }
}
