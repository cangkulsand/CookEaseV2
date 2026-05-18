<?php

namespace Database\Seeders;

use App\Models\Bmi;
use App\Models\User;
use Illuminate\Database\Seeder;

class BmiSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure you have users in the database
        $users = User::all();

        // If no users exist, optionally create some
        if ($users->count() === 0) {
            $users = User::factory()->count(5)->create();
        }

        foreach ($users as $user) {
            Bmi::create([
                'user_id' => $user->id,
                'age' => rand(18, 50),
                'gender' => ['male', 'female'][rand(0, 1)],
                'height' => rand(150, 190), // in cm
                'weight' => rand(50, 100),  // in kg
            ]);
        }
    }
}
