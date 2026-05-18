<?php

namespace Database\Seeders;

use App\Models\Recipe;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Recipe::create([
            'name' => 'Nasi Lemak',
            'description' => 'A traditional Malaysian dish with coconut rice, sambal, and fried anchovies.',
            'duration' => '45 minutes',
            'difficulty' => 'medium',
            'calories' => 600,
            'image' => 'https://cdn.pixabay.com/photo/2015/03/04/12/09/food-658715_1280.jpg',
            'ingredients' => json_encode([
                '2 cups rice',
                '1 cup coconut milk',
                '100g anchovies',
                '2pcs boiled eggs',
            ]),
            'instructions' => 'Wash the rice and soak for 30 minutes. Cook rice with coconut milk and a pinch of salt. Fry anchovies until crispy. Serve with boiled eggs, cucumber, and sambal.',
            'grocery_lists' => json_encode([
                'rice', 'coconut milk', 'anchovies', 'eggs', 'cucumber',
            ]),

        ]);

        Recipe::create([
            'name' => 'Chicken Rendang',
            'description' => 'Spicy and flavorful chicken curry cooked with coconut milk and spices.',
            'duration' => '1 hour 30 minutes',
            'difficulty' => 'hard',
            'calories' => 750,
            'image' => 'https://cdn.pixabay.com/photo/2021/07/07/05/56/rendang-6393322_1280.jpg',
            'ingredients' => json_encode([
                '1 whole chicken (cut into pieces)',
                '2 cups coconut milk',
                '2 stalks lemongrass',
                '1inch galangal',
                '1 tsp turmeric powder',
                '1 tsp coriander powder',
                '4 cloves garlic',
                '2pcs onion',
                '1 inch ginger',
                '1 tsp salt',
                '2 tbsp cooking oil',
            ]),
            'instructions' => 'Blend onion, garlic, ginger, and galangal into a smooth paste. Heat oil in a pot and sauté the blended paste until fragrant. Add chicken pieces and cook until lightly browned. Add coconut milk, lemongrass, and all spices. Stir well. Serve hot with steamed rice.',
            'grocery_lists' => json_encode([
                'chicken', 'coconut milk', 'lemongrass', 'galangal', 'turmeric powder',
                'coriander powder', 'garlic', 'onion', 'ginger', 'salt', 'cooking oil',
            ]),

        ]);

        Recipe::create([
            'name' => 'Mee Goreng Mamak',
            'description' => 'Spicy and savory stir-fried noodles with tofu, potatoes, and bean sprouts.',
            'duration' => '30 minutes',
            'servings' => 2,
            'difficulty' => 'medium',
            'calories' => 550,
            'image' => 'https://images.pexels.com/photos/3054690/pexels-photo-3054690.jpeg',
            'ingredients' => json_encode([
                '200g yellow noodles',
                '100g fried tofu, cubed',
                '1 boiled potato, sliced',
                '1/2 cup bean sprouts',
                '2 cloves garlic, minced',
                '2 tablespoons chili paste',
                '1 tablespoon soy sauce',
                '1 tablespoon ketchup',
                '1 egg',
                'Lime wedges (for garnish)',
            ]),
            'instructions' => 'Heat oil in a wok. Sauté garlic until fragrant. Add chili paste, tofu, and potato slices. Stir-fry for 2 minutes. Add noodles, soy sauce, ketchup, and mix well. Push noodles aside and scramble the egg. Add bean sprouts and stir everything together. Serve hot with lime wedges.',
            'grocery_lists' => json_encode([
                'yellow noodles',
                'fried tofu',
                'potato',
                'bean sprouts',
                'garlic',
                'chili paste',
                'soy sauce',
                'ketchup',
                'egg',
                'lime',
            ]),

        ]);

        Recipe::create([
            'name' => 'Ayam Masak Merah',
            'description' => 'Sweet and spicy tomato-based chicken dish cooked with aromatic spices.',
            'duration' => '45 minutes',
            'servings' => 4,
            'difficulty' => 'medium',
            'calories' => 620,
            'image' => 'https://assets.tmecosys.com/image/upload/t_web767x639/img/recipe/ras/Assets/F45E17AC-75AD-41D4-B3C0-8F0A0EF247DD/Derivates/11d0ce6c-c4cc-4ae8-8860-23105c39bc17.jpg',
            'ingredients' => json_encode([
                '500g chicken, cut into pieces',
                '2 tablespoons chili paste',
                '1 cup tomato puree',
                '1 onion, sliced',
                '2 cloves garlic, minced',
                '1-inch ginger, minced',
                '1 cinnamon stick',
                '2 star anise',
                '1/2 cup water',
                'Salt and sugar to taste',
            ]),
            'instructions' => 'Fry the chicken until golden brown and set aside. In the same oil, sauté onion, garlic, ginger, cinnamon, and star anise until fragrant. Add chili paste and tomato puree, cook until oil separates. Add fried chicken and water. Simmer until sauce thickens. Season with salt and sugar. Serve hot.',
            'grocery_lists' => json_encode([
                'chicken',
                'chili paste',
                'tomato puree',
                'onion',
                'garlic',
                'ginger',
                'cinnamon stick',
                'star anise',
                'salt',
                'sugar',
            ]),

        ]);
    }
}
