<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateRecipesJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IngredientController extends Controller
{
    public function getIngredients()
    {
        // Get all ingredient names from database
        $ingredients = DB::table('ingredients')->pluck('name');

        return response()->json($ingredients);
    }

    public function process(Request $request)
    {
        // ✅ Validate incoming request
        $validated = $request->validate([
            'ingredients' => 'required|string',
            'filters' => 'array',
            'cooking_time' => 'nullable|string',
            'budget' => 'nullable|string',
        ]);

        Log::info('Incoming ingredients raw:', ['raw' => $validated['ingredients']]);

        // ✅ Clean input
        $ingredientsInput = collect(json_decode($validated['ingredients'], true))
            ->pluck('value')
            ->map(function ($item) {
                return preg_replace('/^[^\w\s]+ /u', '', $item);
            })
            ->toArray();

        Log::info('Cleaned ingredients array:', ['array' => $ingredientsInput]);

        // ✅ Load existing ingredients
        $allowedIngredients = DB::table('ingredients')->pluck('name')
            ->map(fn ($n) => strtolower($n))
            ->toArray();

        $cleanedIngredients = [];

        foreach ($ingredientsInput as $ingredient) {
            $ingredient = trim(strtolower($ingredient));

            // Auto-save if new
            if (! in_array($ingredient, $allowedIngredients)) {
                DB::table('ingredients')->insert([
                    'name' => $ingredient,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("✅ New ingredient added to DB: $ingredient");

                $allowedIngredients[] = $ingredient;
            }

            $cleanedIngredients[] = $ingredient;

            // Track usage
            $user = Auth::user();
            $ingredientId = DB::table('ingredients')->where('name', $ingredient)->value('id');

            DB::table('ingredient_usage')->insert([
                'user_id' => $user->id,
                'ingredient_id' => $ingredientId,
                'used_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ✅ Recently Used
        $recentIngredients = DB::table('ingredient_usage')
            ->join('ingredients', 'ingredient_usage.ingredient_id', '=', 'ingredients.id')
            ->where('ingredient_usage.user_id', Auth::id())
            ->orderBy('ingredient_usage.used_at', 'desc')
            ->limit(5)
            ->pluck('ingredients.name');

        // ✅ Frequently Used
        $frequentIngredients = DB::table('ingredient_usage')
            ->join('ingredients', 'ingredient_usage.ingredient_id', '=', 'ingredients.id')
            ->where('ingredient_usage.user_id', Auth::id())
            ->select('ingredients.name', DB::raw('count(*) as total'))
            ->groupBy('ingredients.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // ✅ Prepare for AI
        $ingredients = implode(', ', $cleanedIngredients);
        $filters = $validated['filters'] ?? [];
        $cookingTime = $validated['cooking_time'] ?? '';
        $budget = $validated['budget'] ?? '';
        $apiKey = config('services.groq.key');

        if (! $apiKey) {
            Log::error('Groq API key is missing.');

            return back()->with('message', 'AI service is currently unavailable. Please contact the admin.');
        }

        $user = Auth::user();
        $bmiRecord = $user->bmi;
        $bmiValue = $bmiRecord ? $bmiRecord->getBmiAttribute() : null;
        $bmiCategory = $bmiRecord ? $bmiRecord->getBmiCategory() : null;

        $filterText = implode(', ', $filters);

        $extraInfo = '';
        if ($filterText) {
            $extraInfo .= " Take into account these preferences: $filterText.";
        }
        if ($cookingTime) {
            $extraInfo .= " Try to keep the cooking time around $cookingTime.";
        }
        if ($budget) {
            $extraInfo .= " Ensure the recipes fit a $budget budget.";
        }
        if ($bmiValue) {
            $extraInfo .= " The user has a BMI of $bmiValue ($bmiCategory), so recommend recipes that support a healthy diet for this condition.";
        }

        $prompt = <<<PROMPT
Generate 12 Malaysian recipes using the following ingredients: $ingredients.$extraInfo

Each recipe must be in **JSON format** and include:
- name (string)
- description (string)
- duration (string, e.g. "30 minutes")
- servings (number)
- difficulty (easy/medium/hard)
- calories (estimated total per recipe in kcal)
- image (use any placeholder or leave blank — image will be fetched separately)
- ingredients (array of strings, give detail ingredient's measurement)
- groceryLists (array of strings, ingredient without measurement for grocery shopping list)
- instructions (string, give detail instruction)

Return **only a JSON array** of recipe objects. Do not include any explanation or introduction text.
PROMPT;

        $generationId = (string) Str::uuid();
        $cacheKey = GenerateRecipesJob::cacheKey($user->id, $generationId);

        Cache::put($cacheKey, ['status' => 'pending'], now()->addHour());

        GenerateRecipesJob::dispatch(
            $generationId,
            $user->id,
            $prompt,
            $ingredients,
        );

        return redirect()->route('generate.waiting', ['generationId' => $generationId]);
    }

    public function waiting(string $generationId)
    {
        $cached = Cache::get(GenerateRecipesJob::cacheKey(Auth::id(), $generationId));

        if (! $cached) {
            return redirect()->route('generate')->with('message', 'Recipe generation not found. Please try again.');
        }

        if (($cached['status'] ?? null) === 'complete') {
            return redirect()->route('generate.result', ['generationId' => $generationId]);
        }

        if (($cached['status'] ?? null) === 'failed') {
            return redirect()->route('generate')->with('message', $cached['message'] ?? 'Recipe generation failed.');
        }

        return view('generate-waiting', [
            'generationId' => $generationId,
        ]);
    }

    public function status(string $generationId)
    {
        $cached = Cache::get(GenerateRecipesJob::cacheKey(Auth::id(), $generationId));

        if (! $cached) {
            return response()->json(['status' => 'missing'], 404);
        }

        $payload = ['status' => $cached['status'] ?? 'pending'];

        if (($cached['status'] ?? null) === 'complete') {
            $payload['redirect'] = route('generate.result', ['generationId' => $generationId]);
        } elseif (($cached['status'] ?? null) === 'failed') {
            $payload['message'] = $cached['message'] ?? 'Recipe generation failed.';
        }

        return response()->json($payload);
    }

    public function showResult(string $generationId)
    {
        $cached = Cache::get(GenerateRecipesJob::cacheKey(Auth::id(), $generationId));

        if (! $cached || ($cached['status'] ?? null) !== 'complete') {
            return redirect()->route('generate')->with('message', 'No recipe found. Please try again.');
        }

        $recentIngredients = $this->getGroupedIngredientData(Auth::id())['recent'] ?? [];
        $frequentIngredients = $this->getGroupedIngredientData(Auth::id())['frequent'] ?? [];

        return view('generate-results', [
            'recipes' => $cached['recipes'],
            'ingredients' => $cached['ingredients_used'] ?? '',
            'generationId' => $generationId,
            'recentIngredients' => $recentIngredients,
            'frequentIngredients' => $frequentIngredients,
        ]);
    }

    public function getGroupedIngredients()
    {
        return response()->json([
            [
                'name' => '🕑 Recently Used',
                'items' => array_map(fn ($i) => ['value' => $i], $this->getGroupedIngredientData(Auth::id())['recent']),
            ],
            [
                'name' => '🔥 Frequently Used',
                'items' => array_map(fn ($i) => ['value' => $i], $this->getGroupedIngredientData(Auth::id())['frequent']),
            ],
        ]);
    }

    protected function getGroupedIngredientData($userId)
    {
        $recent = DB::table('ingredient_usage')
            ->join('ingredients', 'ingredient_usage.ingredient_id', '=', 'ingredients.id')
            ->where('ingredient_usage.user_id', $userId)
            ->orderBy('ingredient_usage.used_at', 'desc')
            ->limit(5)
            ->pluck('ingredients.name')
            ->toArray();

        $frequent = DB::table('ingredient_usage')
            ->join('ingredients', 'ingredient_usage.ingredient_id', '=', 'ingredients.id')
            ->where('ingredient_usage.user_id', $userId)
            ->select('ingredients.name', DB::raw('count(*) as total'))
            ->groupBy('ingredients.name')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('ingredients.name')
            ->toArray();

        return compact('recent', 'frequent');
    }

    private function buildExtraInfo($validated, $user)
    {
        $extraInfo = '';
        $filters = $validated['filters'] ?? [];
        $filterText = implode(', ', $filters);
        $healthGoal = $user->healthGoal->goal ?? null;

        if ($filterText) {
            $extraInfo .= " Take into account these preferences: $filterText.";
        }
        if ($healthGoal) {
            $extraInfo .= " The user's health goal is $healthGoal, so recommend recipes that support a healthy diet for this.";
        }
        if ($validated['cooking_time']) {
            $extraInfo .= " Try to keep the cooking time around {$validated['cooking_time']}.";
        }
        if ($validated['budget']) {
            $extraInfo .= " Ensure the recipes fit a {$validated['budget']} budget.";
        }

        $bmi = $user->bmi;
        if ($bmi) {
            $bmiValue = $bmi->getBmiAttribute();
            $category = 'normal';
            if ($bmiValue < 18.5) {
                $category = 'underweight';
            } elseif ($bmiValue >= 25) {
                $category = 'overweight';
            } elseif ($bmiValue >= 30) {
                $category = 'obese';
            }
            $extraInfo .= " The user has a BMI of $bmiValue ($category), so recommend recipes that support a healthy diet for this condition.";
        }

        return $extraInfo;
    }
}
