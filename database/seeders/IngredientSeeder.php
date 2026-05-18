<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $ingredients = [
            // Proteins
            'chicken', 'beef', 'fish', 'tofu', 'shrimp', 'duck', 'squid', 'clams', 'anchovies', 'mackerel', 'sardine', 'crab',

            // Vegetables
            'spinach', 'cabbage', 'carrot', 'potato', 'onion', 'garlic', 'long beans', 'okra', 'eggplant', 'bitter gourd', 'bean sprouts', 'pak choy', 'kangkung', 'tomato',

            // Spices & Herbs
            'lemongrass', 'ginger', 'turmeric', 'coriander', 'cumin', 'fennel', 'cinnamon', 'star anise', 'cloves', 'kaffir lime leaves', 'pandan leaves', 'curry leaves', 'belacan',

            // Pantry Essentials
            'rice', 'coconut milk', 'chili', 'tamarind', 'soy sauce', 'oyster sauce', 'fish sauce', 'palm sugar', 'vinegar', 'sambal', 'peanuts', 'coconut',

            // Carbs & Staples
            'vermicelli noodles', 'yellow noodles', 'rice noodles', 'glutinous rice', 'roti',
        ];

        foreach ($ingredients as $ingredient) {
            DB::table('ingredients')->updateOrInsert(
                ['name' => $ingredient], // condition
                ['created_at' => now(), 'updated_at' => now()] // update/insert
            );
        }

    }
}
