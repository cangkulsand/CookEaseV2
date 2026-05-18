<?php

namespace Database\Seeders;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MealPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $recipes = Recipe::all();

        if (! $user || $recipes->count() < 3) {
            $this->command->info('Make sure you have at least 1 user and 3 recipes seeded.');

            return;
        }

        $dates = [
            Carbon::today(),
            Carbon::tomorrow(),
            Carbon::today()->addDays(2),
        ];

        $mealTypes = ['breakfast', 'lunch', 'dinner'];

        foreach ($dates as $i => $date) {
            foreach ($mealTypes as $j => $type) {
                MealPlan::create([
                    'user_id' => $user->id,
                    'recipe_id' => $recipes->random()->id,
                    'date' => $date,
                    'meal_type' => $type,
                ]);
            }
        }
    }
}
