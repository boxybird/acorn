<?php

namespace App\Http\Controllers\Staff;

use App\Enums\IntakeStatus;
use App\Http\Controllers\Controller;
use App\Models\Intake;
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
}
