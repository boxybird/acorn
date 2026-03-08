<?php

namespace App\Jobs;

use App\Models\FormResponse;
use App\Models\Intake;
use App\Services\FormSchemaService;
use App\Services\MondayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncIntakeToMonday implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly Intake $intake) {}

    public function handle(MondayService $mondayService, FormSchemaService $formSchemaService): void
    {
        $this->intake->update(['sync_status' => 'syncing']);

        $columnValues = $this->buildColumnValues($formSchemaService);

        /** @var \App\Models\Patient $patient */
        $patient = $this->intake->patient;
        $itemId = $mondayService->createItem($patient, $columnValues);

        $documents = array_values($this->intake->documents->all());

        if ($documents !== []) {
            $mondayService->uploadFiles($itemId, $documents);
        }

        $this->intake->update([
            'sync_status' => 'synced',
            'synced_at' => now(),
        ]);
    }

    public function failed(Throwable $throwable): void
    {
        Log::error('Monday.com sync failed for intake', [
            'intake_id' => $this->intake->id,
            'error' => $throwable->getMessage(),
        ]);

        $this->intake->update(['sync_status' => 'failed']);
    }

    /**
     * @return array<string, string>
     */
    private function buildColumnValues(FormSchemaService $formSchemaService): array
    {
        $columnValues = [];

        /** @var \Illuminate\Database\Eloquent\Collection<int, FormResponse> $responses */
        $responses = $this->intake->formResponses;

        foreach ($responses as $response) {
            $schema = $formSchemaService->get($response->schema_key);

            if ($schema === null) {
                continue;
            }

            /** @var list<array{fields: list<array{key: string, monday_field?: string}>}> $sections */
            $sections = $schema['sections'];

            foreach ($sections as $section) {
                foreach ($section['fields'] as $field) {
                    if (isset($field['monday_field'])) {
                        /** @var array<string, mixed> $data */
                        $data = $response->data ?? [];
                        $value = $data[$field['key']] ?? null;

                        if ($value !== null && (is_string($value) || is_numeric($value))) {
                            $columnValues[$field['monday_field']] = (string) $value;
                        }
                    }
                }
            }
        }

        return $columnValues;
    }
}
