<?php

namespace App\Services;

use App\Models\Patient;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Str;

class MagicLinkService
{
    public function send(Patient $patient): void
    {
        $token = Str::random(64);

        $patient->update([
            'magic_link_token' => $token,
            'magic_link_expires_at' => now()->addMinutes(30),
        ]);

        $patient->notify(new MagicLinkNotification($token));
    }

    public function sendToEmail(string $email): void
    {
        $patient = Patient::query()->firstOrCreate(
            ['email' => $email],
        );

        $this->send($patient);
    }
}
