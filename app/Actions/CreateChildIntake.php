<?php

namespace App\Actions;

use App\Enums\FormResponseStatus;
use App\Models\FormResponse;
use App\Models\Intake;

class CreateChildIntake
{
    /** @var list<string> */
    private const array PARENT_LEVEL_SCHEMAS = ['demographics', 'insurance'];

    public function handle(int $patientId): Intake
    {
        $newIntake = Intake::query()->create(['patient_id' => $patientId]);

        $mostRecentIntake = Intake::query()
            ->where('patient_id', $patientId)
            ->where('id', '!=', $newIntake->id)
            ->latest('id')
            ->first();

        if ($mostRecentIntake) {
            $parentFormResponses = FormResponse::query()
                ->where('intake_id', $mostRecentIntake->id)
                ->whereIn('schema_key', self::PARENT_LEVEL_SCHEMAS)
                ->get();

            foreach ($parentFormResponses as $parentFormResponse) {
                FormResponse::query()->create([
                    'intake_id' => $newIntake->id,
                    'schema_key' => $parentFormResponse->schema_key,
                    'data' => $parentFormResponse->data,
                    'status' => FormResponseStatus::InProgress,
                ]);
            }
        }

        return $newIntake;
    }
}
