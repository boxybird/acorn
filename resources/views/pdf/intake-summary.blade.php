<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Intake Summary</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 16px; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-top: 24px; }
        .header { margin-bottom: 24px; }
        .meta { color: #666; font-size: 11px; }
        .field { margin-bottom: 8px; }
        .field-label { font-weight: bold; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Intake Summary</h1>
        <p class="meta">Generated {{ now()->format('F j, Y') }}</p>
        <p><strong>Child:</strong> {{ $intake->child_name ?? '—' }}</p>
        <p><strong>Parent:</strong> {{ $intake->patient->name ?? $intake->patient->email }}</p>
        <p><strong>Status:</strong> {{ $intake->status->label() }}</p>
        <p><strong>Submitted:</strong> {{ $intake->created_at->format('F j, Y') }}</p>
    </div>

    @foreach ($schemas as $schema)
        @php
            $response = $intake->formResponses->firstWhere('schema_key', $schema['key']);
        @endphp
        <h2>{{ __($schema['title']) }}</h2>
        @if ($response)
            @foreach ($response->data ?? [] as $key => $value)
                <div class="field">
                    <span class="field-label">{{ str($key)->replace('_', ' ')->title() }}:</span>
                    @if (is_array($value))
                        {{ implode(', ', $value) }}
                    @else
                        {{ $value }}
                    @endif
                </div>
            @endforeach
        @else
            <p class="meta">Not completed</p>
        @endif
    @endforeach
</body>
</html>
