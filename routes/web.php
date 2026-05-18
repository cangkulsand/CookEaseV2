<?php

use App\Jobs\GenerateRecipesJob;
use App\Models\Recipe;
use App\Models\Review;
use App\Models\Favorite;
use App\Models\MealPlan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BMIController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenerateController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthGoalController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\NotificationController;

// Authenticated user routes
Route::middleware(['auth'])->group(function () {

    // BMI
    Route::get('/profile/bmi', [ProfileController::class, 'modify'])->name('profile.bmi.edit');
    Route::get('/bmi/form', [BMIController::class, 'showForm'])->name('bmi.form');
    Route::post('/bmi/store', [BMIController::class, 'store'])->name('profile.bmi.update');
    Route::post('/bmi/update', [BMIController::class, 'update'])->name('bmi.update');
    Route::get('/health-goals', [HealthGoalController::class, 'create'])->name('health_goals.create');
    Route::post('/health-goals', [HealthGoalController::class, 'store'])->name('health_goals.store');
    Route::get('/profile/health-goal', [HealthGoalController::class, 'show'])->name('health_goals.show');
    Route::put('/profile/health-goal', [HealthGoalController::class, 'update'])->name('health_goals.update');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Recipe Generation Flow
    Route::get('/generate', [GenerateController::class, 'showForm'])->name('generate');
    Route::post('/generate', [IngredientController::class, 'process'])->name('generate.process');
    Route::get('/generating/{generationId}', [IngredientController::class, 'waiting'])->name('generate.waiting');
    Route::get('/generate-status/{generationId}', [IngredientController::class, 'status'])->name('generate.status');
    Route::get('/generate-result/{generationId}', [IngredientController::class, 'showResult'])->name('generate.result');

    // API for frontend Tagify
    Route::get('/api/ingredients', [IngredientController::class, 'getIngredients']);

    // Recipe List and Detail
    Route::get('/browse-recipes', [RecipeController::class, 'browse'])->name('recipes.browse');
    Route::post('/review', [ReviewController::class, 'store'])->name('review.store');
    Route::get('/recipe-detail/{index}', function ($index) {
        $from = request()->query('from');
        $isSession = $from === 'session' || $from === 'generation';
        $user = Auth::user();
        $isFavorited = false;
        $isPlanned = false;
        $isReviewed = false;
        $recipeId = null;
        $reviews = [];

        if ($isSession) {
            if ($from === 'generation') {
                $generationId = request()->query('id');
                $cached = $generationId
                    ? Cache::get(GenerateRecipesJob::cacheKey(Auth::id(), $generationId))
                    : null;
                $recipes = ($cached['status'] ?? null) === 'complete' ? ($cached['recipes'] ?? []) : [];
            } else {
                $recipes = session('generated_recipes', []);
            }

            if (!isset($recipes[$index])) {
                abort(404);
            }
            $recipe = $recipes[$index];

            // Ensure ingredients and groceryLists are arrays
            $recipe['ingredients'] = is_array($recipe['ingredients'])
                ? $recipe['ingredients']
                : json_decode($recipe['ingredients'], true) ?? [];

            $recipe['groceryLists'] = is_array($recipe['groceryLists'])
                ? $recipe['groceryLists']
                : json_decode($recipe['groceryLists'], true) ?? [];


            $existing = Recipe::where('name', $recipe['name'])
                ->where('description', $recipe['description'])
                ->first();

            if ($existing && $user) {
                $recipeId = $existing->id;

                // Auto-remove past meal plans for this user
                MealPlan::where('user_id', $user->id)
                ->whereDate('date', '<', now()->toDateString())
                ->delete();

                $isFavorited = Favorite::where('user_id', $user->id)
                    ->where('recipe_id', $recipeId)
                    ->exists();

                $isPlanned = MealPlan::where('user_id', $user->id)
                    ->where('recipe_id', $recipeId)
                    ->exists();

                $reviews = Review::where('recipe_id', $recipeId)->latest()->get();
                $isReviewed = Review::where('recipe_id', $recipeId)
                    ->where('user_id', $user->id)
                    ->exists();
            }

            return view('recipe-detail', [
                'recipe' => $recipe,
                'isSession' => true,
                'isFavorited' => $isFavorited,
                'isPlanned' => $isPlanned,
                'recipeId' => $recipeId,
                'reviews' => $reviews,
                'isReviewed' => $isReviewed,
            ]);
        }

        $recipe = Recipe::findOrFail($index);

        $ingredients = $recipe->ingredients;
        $groceryLists = $recipe->grocery_lists;

        // Defensive decoding
        $decodedIngredients = is_array($ingredients)
            ? $ingredients
            : (json_decode($ingredients, true) ?? []);

        $decodedGroceryLists = is_array($groceryLists)
            ? $groceryLists
            : (json_decode($groceryLists, true) ?? []);

        $recipeArray = [
            'name' => $recipe->name,
            'description' => $recipe->description,
            'duration' => $recipe->duration,
            'servings' => $recipe->servings,
            'difficulty' => $recipe->difficulty,
            'calories' => $recipe->calories,
            'image' => $recipe->image,
            'ingredients' => $decodedIngredients,
            'instructions' => $recipe->instructions,
            'groceryLists' => $decodedGroceryLists,
        ];

        if ($user) {
            // Auto-remove past meal plans for this user
            MealPlan::where('user_id', $user->id)
            ->whereDate('date', '<', now()->toDateString())
            ->delete();

            $isFavorited = Favorite::where('user_id', $user->id)
                ->where('recipe_id', $recipe->id)
                ->exists();

            $isPlanned = MealPlan::where('user_id', $user->id)
                ->where('recipe_id', $recipe->id)
                ->exists();

            $isReviewed = Review::where('recipe_id', $recipe->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        $reviews = Review::where('recipe_id', $recipe->id)->latest()->get();

        return view('recipe-detail', [
            'recipe' => $recipeArray,
            'isSession' => false,
            'isFavorited' => $isFavorited,
            'recipeId' => $recipe->id,
            'isPlanned' => $isPlanned,
            'reviews' => $reviews,
            'isReviewed' => $isReviewed,
        ]);
    })->name('recipe.detail');

    // Save/Unsave recipe
    Route::post('/save-recipe', [RecipeController::class, 'saveRecipe'])->name('recipe.save');
    Route::delete('/recipe/unsave/{id}', [RecipeController::class, 'unsaveRecipe'])->name('recipe.unsave');
    Route::get('/saved-recipes', [FavoriteController::class, 'saved'])->name('recipes.saved');

    // My Meals Plan
    Route::get('/meal-plan', [MealPlanController::class, 'index'])->name('meal-plan.index');
    Route::post('/meal-plan', [MealPlanController::class, 'store'])->name('meal-plan.store');
    Route::delete('/meal-plan/{mealPlan}', [MealPlanController::class, 'destroy'])->name('meal-plan.destroy');
    Route::post('/meal-plan/add', [MealPlanController::class, 'storeMeal'])->name('meal-plan.add');
    Route::post('/meal-plan/store-generated', [MealPlanController::class, 'storeFromGenerated'])->name('meal-plan.storeGenerated');
    Route::get('/recipe-saved/{id}', [MealPlanController::class, 'showSaved'])->name('recipe.showSaved');
    Route::get('/recipe-saved/{id}', [MealPlanController::class, 'showSaved'])->name('recipe.saved.detail');
    Route::get('/meal-plan/{id}/edit', [MealPlanController::class, 'edit'])->name('meal-plan.edit');
    Route::put('/meal-plan/{id}', [MealPlanController::class, 'update'])->name('meal-plan.update');

    // Notifications mark as read route
    Route::patch('/notifications/{notificationId}/mark', [MealPlanController::class, 'markNotificationAsRead'])
        ->middleware('auth')
        ->name('notifications.mark'); 
});

Route::get('/run-migrations', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return Artisan::output();  // Show artisan output directly
    } catch (\Throwable $e) {
        return response()->make(
            "<h2>❌ Error running migration:</h2><pre>" . $e->getMessage() . "</pre><hr><pre>" . $e->getTraceAsString() . "</pre>",
            500
        );
    }
});

// Landing and OAuth
Route::get('/', fn() => redirect()->route('login'));

Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// Dashboard
Route::get('/dashboard', fn() => view('dashboard'))->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

// Dev-only testing view (optional)
Route::get('/test-filters', fn() => view('test-filters'));
Route::get('/send-meal-notifications', [NotificationController::class, 'sendTodayMealPlanNotifications']);

// Require auth routes generated by Breeze or your auth scaffolding
require __DIR__ . '/auth.php';
