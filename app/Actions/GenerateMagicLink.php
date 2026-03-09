<?php

namespace App\Actions;

use App\Models\Patient;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Str;

class GenerateMagicLink
{
    public function handle(Patient $patient): void
    {
        $token = Str::random(64);

        $patient->update([
            'magic_link_token' => $token,
            'magic_link_expires_at' => now()->addMinutes(30),
        ]);

        $patient->notify(new MagicLinkNotification($token));
    }

    public function handleForEmail(string $email): void
    {
        $patient = Patient::query()->whereBlindIndex('email', $email)->first();

        if (! $patient instanceof Patient) {
            $patient = Patient::query()->create(['email' => $email]);
        }

        $this->handle($patient);
    }
}
