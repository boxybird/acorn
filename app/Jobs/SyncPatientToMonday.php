<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\Patient;
use App\Services\FormSchemaService;
use App\Services\MondayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncPatientToMonday implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly Patient $patient) {}

    public function handle(MondayService $mondayService, FormSchemaService $formSchemaService): void
    {
        $this->patient->update(['sync_status' => 'syncing']);

        $columnValues = $this->buildColumnValues($formSchemaService);

        $itemId = $mondayService->createItem($this->patient, $columnValues);

        /** @var list<Document> $documents */
        $documents = array_values(
            Document::query()
                ->whereIn('intake_id', $this->patient->intakes()->pluck('id'))
                ->get()
                ->all(),
        );

        if ($documents !== []) {
            $mondayService->uploadFiles($itemId, $documents);
        }

        $this->patient->update([
            'sync_status' => 'synced',
            'synced_at' => now(),
        ]);
    }

    public function failed(Throwable $throwable): void
    {
        $this->patient->update(['sync_status' => 'failed']);
    }

    /**
     * @return array<string, string>
     */
    private function buildColumnValues(FormSchemaService $formSchemaService): array
    {
        $columnValues = [];
        $responses = \App\Models\FormResponse::query()
            ->whereIn('intake_id', $this->patient->intakes()->pluck('id'))
            ->get();

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
