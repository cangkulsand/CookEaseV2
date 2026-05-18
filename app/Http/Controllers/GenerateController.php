<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GenerateController extends Controller
{
    public function process(Request $request)
    {
        $userId = Auth::id();
        $rawIngredients = $request->input('ingredients');
        $userId = Auth::id();
        $rawIngredients = $request->input('ingredients');

        // Decode Tagify input if it's in JSON format (e.g. [{"value":"chicken"}, {"value":"tofu"}])
        $ingredientsArray = collect(json_decode($rawIngredients, true))
            ->pluck('value')
            ->map(fn ($item) => trim(strtolower($item)))
            ->filter()
            ->unique()
            ->toArray();

        foreach ($ingredientsArray as $ingredientName) {
            // Check if ingredient exists
            $ingredient = DB::table('ingredients')->where('name', $ingredientName)->first();

            if (! $ingredient) {
                // Insert new ingredient
                $ingredientId = DB::table('ingredients')->insertGetId([
                    'name' => $ingredientName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $ingredientId = $ingredient->id;
            }

            // Insert usage record
            DB::table('ingredient_usage')->insert([
                'user_id' => $userId,
                'ingredient_id' => $ingredientId,
                'used_at' => now(),
            ]);
        }

        return back()->with('message', 'Ingredients saved successfully.');

    }

    public function showForm()
    {
        $userId = Auth::id(); // safer kalau Auth::user() not logged in

        // Get recent ingredients used by the user (last 5)

        $recentIngredients = DB::table('ingredient_usage')
            ->join('ingredients', 'ingredient_usage.ingredient_id', '=', 'ingredients.id')
            ->where('ingredient_usage.user_id', $userId)
            ->select('ingredients.name')
            ->orderBy('ingredient_usage.used_at', 'desc')
            ->limit(5)
            ->pluck('ingredients.name')
            ->toArray();

        // Get most frequent ingredients used by the user
        $frequentIngredients = DB::table('ingredient_usage')
            ->join('ingredients', 'ingredient_usage.ingredient_id', '=', 'ingredients.id')
            ->where('ingredient_usage.user_id', Auth::id())
            ->select('ingredients.name', DB::raw('count(*) as total'))
            ->groupBy('ingredients.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get(); // 💡 Leave as collection to pluck in Blade

        // Get most frequent ingredients used by the user
        $frequentIngredients = DB::table('ingredient_usage')
            ->join('ingredients', 'ingredient_usage.ingredient_id', '=', 'ingredients.id')
            ->where('ingredient_usage.user_id', Auth::id())
            ->select('ingredients.name', DB::raw('count(*) as total'))
            ->groupBy('ingredients.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get(); // 💡 Leave as collection to pluck in Blade

        $frequentNames = $frequentIngredients->pluck('name')->toArray(); // NEW variable for Blade

        return view('generate', [
            'recentIngredients' => $recentIngredients,
            'frequentIngredients' => $frequentIngredients,
            'frequentNames' => $frequentNames,
        ]);
    }
}
