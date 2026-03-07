<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Patient;
use Illuminate\Support\Facades\Http;

class MondayService
{
    public function __construct(
        private readonly string $apiToken,
        private readonly string $boardId,
    ) {}

    /**
     * @param  array<string, string>  $columnValues
     */
    public function createItem(Patient $patient, array $columnValues): string
    {
        $columnValuesJson = json_encode($columnValues);

        $mutation = <<<GRAPHQL
            mutation {
                create_item (
                    board_id: {$this->boardId},
                    item_name: "{$patient->name} ({$patient->email})",
                    column_values: "{$this->escapeGraphQL((string) $columnValuesJson)}"
                ) {
                    id
                }
            }
        GRAPHQL;

        /** @var array{data: array{create_item: array{id: string}}} $response */
        $response = Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.monday.com/v2', [
            'query' => $mutation,
        ])->throw()->json();

        return $response['data']['create_item']['id'];
    }

    /**
     * @param  list<Document>  $documents
     */
    public function uploadFiles(string $itemId, array $documents): void
    {
        foreach ($documents as $document) {
            $filePath = storage_path('app/private/'.$document->file_path);

            if (! file_exists($filePath)) {
                continue;
            }

            $mutation = 'mutation ($file: File!) { add_file_to_column (item_id: '.$itemId.', column_id: "files", file: $file) { id } }';

            Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->attach('variables[file]', (string) file_get_contents($filePath), $document->original_filename)
                ->post('https://api.monday.com/v2/file', [
                    'query' => $mutation,
                ])->throw();
        }
    }

    private function escapeGraphQL(string $value): string
    {
        return str_replace(['"', '\\'], ['\\"', '\\\\'], $value);
    }
}
