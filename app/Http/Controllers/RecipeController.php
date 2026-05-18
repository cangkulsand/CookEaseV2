<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Recipe;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    public function browse(Request $request)
    {
        $query = Recipe::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('calories_min')) {
            $query->where('calories', '>=', (int) $request->calories_min);
        }

        if ($request->filled('calories_max')) {
            $query->where('calories', '<=', (int) $request->calories_max);
        }

        $recipes = $query->latest()->paginate(9)->appends($request->all());

        return view('browse-recipes', compact('recipes'));
    }

    public function saveRecipe(Request $request)
    {
        $data = $request->all();

        $ingredients = is_string($data['ingredients']) ? json_decode($data['ingredients'], true) : $data['ingredients'];
        $groceryLists = is_string($data['groceryLists']) ? json_decode($data['groceryLists'], true) : $data['groceryLists'];

        $imageUrl = $data['image'];
        $localImagePath = null;

        try {
            if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                // Generate a hash-based filename (consistent but unique to URL)
                $hashedName = md5($imageUrl);
                $filename = "recipes/{$hashedName}.jpg";

                // Only download if it doesn't exist
                if (! Storage::disk('public')->exists($filename)) {
                    $imageContent = Http::get($imageUrl)->body();
                    Storage::disk('public')->put($filename, $imageContent);
                }

                $localImagePath = Storage::url($filename); // /storage/recipes/xxxx.jpg
            }
        } catch (\Exception $e) {
            Log::error('Failed to download recipe image: '.$e->getMessage());
            $localImagePath = 'https://via.placeholder.com/300x200';
        }

        // Save or create recipe
        $recipe = Recipe::firstOrCreate(
            ['name' => $data['name'], 'description' => $data['description']],
            [
                'duration' => $data['duration'] ?? null,
                'servings' => $data['servings'] ?? null,
                'difficulty' => $data['difficulty'] ?? 'easy',
                'calories' => $data['calories'] ?? null,
                'image' => $localImagePath,
                'instructions' => $data['instructions'],
                'ingredients' => json_encode($ingredients),
                'grocery_lists' => json_encode($groceryLists),
            ]
        );

        Favorite::firstOrCreate([
            'user_id' => Auth::id(),
            'recipe_id' => $recipe->id,
        ]);

        return redirect()->route('recipe.detail', [
            'index' => $recipe->id,
            'from' => 'db',
        ])->with('message', 'Recipe saved to favorites!');
    }

    public function unsaveRecipe($id)
    {
        $user = Auth::user();

        // Delete favorite entry
        Favorite::where('user_id', $user->id)
            ->where('recipe_id', $id)
            ->delete();

        return back()->with('message', 'Recipe removed from favorites.');
    }

    public function show($id)
    {
        $recipe = Recipe::findOrFail($id);
        $reviews = Review::where('recipe_id', $id)->get();

        return view('recipe-detail', compact('recipe', 'reviews'));

    }
}
