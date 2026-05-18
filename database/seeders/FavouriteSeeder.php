<?php

namespace Database\Seeders;

use App\Models\Favorite;
use Illuminate\Database\Seeder;

class FavouriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Favorite::create([
            'user_id' => 1,
            'recipe_id' => 1,
        ]);

        Favorite::create([
            'user_id' => 1,
            'recipe_id' => 2,
        ]);
    }
}
