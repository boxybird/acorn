<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        <meta name="csrf-token" content="{{ csrf_token() }}">

        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        @if(config('demo.enabled'))
            <script id="demo-data" type="application/json">
                @php
                    $patients = \App\Models\Patient::with('intakes.formResponses')->get();
                    $users = \App\Models\User::all();
                @endphp
                {!! json_encode([
                    'patients' => $patients->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'intakes' => $p->intakes->map(fn ($i) => [
                            'child_name' => $i->child_name ?: 'Not yet named',
                            'status' => $i->status->label(),
                            'form_count' => $i->formResponses->count(),
                            'completed_count' => $i->formResponses->where('status', \App\Enums\FormResponseStatus::Completed)->count(),
                        ])->all(),
                    ])->all(),
                    'users' => $users->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                    ])->all(),
                ]) !!}
            </script>
            <div id="demo-panel"></div>
            @vite(['resources/js/demo.ts'])
        @endif
    </body>
</html>
