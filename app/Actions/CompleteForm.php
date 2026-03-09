<?php

namespace App\Actions;

use App\Enums\IntakeStatus;
use App\Jobs\SyncIntakeToMonday;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\User;
use App\Notifications\CorrectionSubmittedNotification;
use App\Services\FormSchemaService;
use Illuminate\Support\Facades\Notification;

class CompleteForm
{
    public function __construct(
        private FormSchemaService $formSchemaService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(int $intakeId, string $schemaKey, array $data): FormResponse
    {
        $formResponse = FormResponse::query()->updateOrCreate(
            ['intake_id' => $intakeId, 'schema_key' => $schemaKey],
            ['data' => $data, 'status' => 'completed'],
        );

        if ($schemaKey === 'child_information') {
            $this->extractChildName($intakeId, $data);
        }

        $intake = Intake::query()->findOrFail($intakeId);

        if ($intake->status === IntakeStatus::Flagged) {
            $intake->update(['status' => IntakeStatus::CorrectionSubmitted]);

            $staffUsers = User::all();
            Notification::send($staffUsers, new CorrectionSubmittedNotification($intake));
        }

        $this->checkAndDispatchSync($intakeId);

        return $formResponse;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractChildName(int $intakeId, array $data): void
    {
        /** @var string|null $firstName */
        $firstName = $data['child_first_name'] ?? null;
        /** @var string|null $lastName */
        $lastName = $data['child_last_name'] ?? null;

        $childName = trim(($firstName ?? '').' '.($lastName ?? ''));

        if ($childName !== '') {
            /** @var Intake $intake */
            $intake = Intake::query()->findOrFail($intakeId);
            $intake->update(['child_name' => $childName]);
        }
    }

    private function checkAndDispatchSync(int $intakeId): void
    {
        $totalSchemas = count($this->formSchemaService->all());
        $completedCount = FormResponse::query()
            ->where('intake_id', $intakeId)
            ->where('status', 'completed')
            ->count();

        if ($completedCount >= $totalSchemas) {
            /** @var Intake $intake */
            $intake = Intake::query()->findOrFail($intakeId);
            $intake->update(['status' => IntakeStatus::Submitted]);

            if (config('services.monday.api_token')) {
                SyncIntakeToMonday::dispatch($intake);
            }
        }
    }
}
