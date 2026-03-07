<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Dr. Laura Chen',
            'email' => 'laura.chen@jumpstartautism.com',
        ]);

        User::factory()->create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus.rivera@jumpstartautism.com',
        ]);

        $this->call(PatientSeeder::class);
    }
}
