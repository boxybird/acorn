<?php

use App\Models\Document;
use App\Models\Patient;
use App\Services\MondayService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('createItem sends correct mutation and returns item id', function (): void {
    Http::fake([
        'api.monday.com/v2' => Http::response([
            'data' => ['create_item' => ['id' => '98765']],
        ]),
    ]);

    $patient = Patient::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $service = new MondayService('test-token', '12345');
    $itemId = $service->createItem($patient, ['status' => 'New']);

    expect($itemId)->toBe('98765');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.monday.com/v2'
            && $request->hasHeader('Authorization', 'test-token')
            && str_contains($request['query'], 'create_item')
            && str_contains($request['query'], 'board_id: 12345')
            && str_contains($request['query'], 'Jane Doe (jane@example.com)');
    });
});

test('createItem escapes quotes and backslashes in column values', function (): void {
    Http::fake([
        'api.monday.com/v2' => Http::response([
            'data' => ['create_item' => ['id' => '11111']],
        ]),
    ]);

    $patient = Patient::factory()->create();

    $service = new MondayService('test-token', '12345');
    $itemId = $service->createItem($patient, [
        'notes' => 'She said "hello" and used a \\ backslash',
    ]);

    expect($itemId)->toBe('11111');

    Http::assertSent(function (Request $request): bool {
        $query = $request['query'];

        // json_encode produces \" around values, then escapeGraphQL escapes those to \\"
        // The column_values string should contain escaped quotes and backslashes
        return str_contains($query, 'column_values')
            && str_contains($query, 'hello')
            && str_contains($query, 'backslash')
            && str_contains($query, 'create_item');
    });
});

test('uploadFiles sends a file for each document', function (): void {
    Http::fake([
        'api.monday.com/v2/file' => Http::response(['data' => ['add_file_to_column' => ['id' => '1']]]),
    ]);

    $documents = Document::factory()->count(2)->create();

    foreach ($documents as $document) {
        $fullPath = storage_path('app/private/'.$document->file_path);
        $directory = dirname($fullPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($fullPath, 'fake file content');
    }

    $service = new MondayService('test-token', '12345');
    $service->uploadFiles('98765', $documents->all());

    Http::assertSentCount(2);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.monday.com/v2/file'
            && $request->hasHeader('Authorization', 'test-token');
    });

    // Clean up temp files
    foreach ($documents as $document) {
        @unlink(storage_path('app/private/'.$document->file_path));
    }
});

test('uploadFiles skips documents whose files do not exist', function (): void {
    Http::fake();

    $document = Document::factory()->create([
        'file_path' => 'documents/nonexistent-file.pdf',
    ]);

    $service = new MondayService('test-token', '12345');
    $service->uploadFiles('98765', [$document]);

    Http::assertNothingSent();
});
