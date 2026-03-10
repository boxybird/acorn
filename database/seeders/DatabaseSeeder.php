<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Sandi Keeton',
            'email' => 'sandi.keeton@jumpstartaba.com',
        ]);

        User::factory()->create([
            'name' => 'Andrea Perea',
            'email' => 'andrea.perea@jumpstartaba.com',
        ]);

        $this->call(PatientSeeder::class);
    }
}
