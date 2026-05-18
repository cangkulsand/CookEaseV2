<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Notifications\DailyMealPlanReminder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class MealPlanController extends Controller
{
    // Show meal plan for selected day
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Only show meal plans from today onwards
        $availableDates = MealPlan::where('user_id', $userId)
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->pluck('date')
            ->unique()
            ->toArray();

        // Get selected date from query, else fallback smartly
        $selectedDate = $request->input('date');

        if (! $selectedDate || ! in_array($selectedDate, $availableDates)) {
            $selectedDate = $availableDates[0] ?? Carbon::today()->toDateString(); // fallback to today if no plans
        }

        // Load meal plans for the selected date
        $plans = MealPlan::with('recipe')
            ->where('user_id', $userId)
            ->whereDate('date', $selectedDate)
            ->orderBy('meal_type')
            ->get()
            ->groupBy('meal_type');

        $recipes = Recipe::all();

        return view('meal_plan.index', [
            'plans' => $plans,
            'recipes' => $recipes,
            'date' => $selectedDate,
            'availableDates' => $availableDates,
        ]);
    }

    public function storeMeal(Request $request)
    {
        $request->validate([
            'meal_type' => 'required|in:breakfast,lunch,dinner,snack',
            'date' => 'required|date',
        ]);

        $data = $request->all();
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

        $recipe = Recipe::firstOrCreate(
            ['name' => $data['name'], 'description' => $data['description']],
            [
                'duration' => $data['duration'] ?? null,
                'servings' => $data['servings'] ?? null,
                'difficulty' => $data['difficulty'] ?? 'easy',
                'calories' => $data['calories'] ?? null,
                'image' => $localImagePath,
                'ingredients' => json_encode($data['ingredients']),
                'instructions' => $data['instructions'],
                'grocery_lists' => json_encode($data['groceryLists']),
            ]
        );

        MealPlan::create([
            'user_id' => Auth::id(),
            'recipe_id' => $recipe->id,
            'meal_type' => $data['meal_type'],
            'date' => $data['date'],
        ]);

        // Only send notification if the meal plan date is today
        if ($data['date'] === now()->toDateString()) {
            $user = auth()->user();
            $user->notify(new DailyMealPlanReminder($data['meal_type'], $user->name));
        }

        return back()->with('message', 'Recipe added to your meal plan!');
    }

    public function store(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'date' => 'required|date',
            'meal_type' => 'required|in:breakfast,lunch,dinner,snack',
        ]);

        $mealPlan = MealPlan::create([
            'user_id' => Auth::id(),
            'recipe_id' => $request->recipe_id,
            'date' => $request->date,
            'meal_type' => $request->meal_type,
        ]);

        if ($request->date === now()->toDateString()) {
            $user = auth()->user();
            $user->notify(new DailyMealPlanReminder($request->meal_type, $user->name));
        }

        return back()->with('message', 'Meal plan added successfully.');
    }

    public function storeFromGenerated(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'instructions' => 'required|string',
            'ingredients' => 'required|array',
            'duration' => 'nullable|string',
            'difficulty' => 'nullable|string',
            'servings' => 'nullable|integer',
            'calories' => 'nullable|integer',
            'image' => 'nullable|string',
            'date' => 'required|date',
            'meal_type' => 'required|in:breakfast,lunch,dinner,snack',
        ]);

        $duplicate = MealPlan::where('user_id', Auth::id())
            ->whereDate('date', $request->date)
            ->where('meal_type', $request->meal_type)
            ->whereHas('recipe', function ($query) use ($request) {
                $query->where('name', $request->name);
            })
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'This recipe has already been added for '.ucfirst($request->meal_type).' on this date.',
            ], 422);
        }

        $ingredientNames = collect($request->ingredients)->pluck('value')->toArray();

        $recipe = Recipe::create([
            'name' => $request->name,
            'description' => $request->description,
            'instructions' => $request->instructions,
            'ingredients' => json_encode($request->ingredients),
            'grocery_lists' => json_encode($ingredientNames),
            'duration' => $request->duration,
            'difficulty' => $request->difficulty,
            'servings' => $request->servings,
            'calories' => $request->calories,
            'image' => $request->image,
        ]);

        MealPlan::create([
            'user_id' => Auth::id(),
            'recipe_id' => $recipe->id,
            'date' => $request->date,
            'meal_type' => $request->meal_type,
        ]);

        if ($request->date === now()->toDateString()) {
            $user = auth()->user();
            $user->notify(new DailyMealPlanReminder($request->meal_type, $user->name));
        }

        return response()->json(['success' => true, 'message' => 'Meal plan added successfully.']);
    }

    public function showSaved($id)
    {
        $recipe = Recipe::findOrFail($id);
        $recipe->ingredients = json_decode($recipe->ingredients, true);
        $recipe->groceryLists = json_decode($recipe->groceryLists ?? '[]', true);

        return view('recipe-saved-detail', ['recipe' => $recipe]);
    }

    public function edit($id)
    {
        $meal = MealPlan::findOrFail($id);
        $recipes = Recipe::all();

        return view('meal-plan.edit', compact('meal', 'recipes'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'meal_type' => 'required|in:breakfast,lunch,dinner,snack',
        ]);

        $meal = MealPlan::findOrFail($id);
        $meal->update($validated);

        return redirect()->route('meal-plan.index', ['date' => $validated['date']])
            ->with('message', 'Meal updated successfully!');
    }

    public function destroy(MealPlan $mealPlan)
    {
        $mealPlan->delete();

        return back()->with('message', 'Meal removed successfully.');
    }

    public function markNotificationAsRead($notificationId)
    {
        $user = auth()->user();

        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return redirect()->back()->with('error', 'Notification not found.');
        }

        $notification->markAsRead();

        return redirect()->back()->with('success', 'Notification marked as read.');
    }
}
