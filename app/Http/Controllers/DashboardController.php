<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\BMI;
use App\Models\Favorite;
use App\Models\MealPlan;
use App\Models\Recipe;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // BMI
        $bmiRecord = $user->bmi;
        //$bmi = $bmiRecord ? number_format($bmiRecord->getBmiAttribute(), 1) : null;
        $bmiRaw = $bmiRecord?->getBmiAttribute();
        $bmi = $bmiRaw ? number_format($bmiRaw, 1) : 'N/A';

        $bmiCategory = $bmiRecord ? $bmiRecord->getBmiCategory() : 'N/A';

        // Calories Intake Today
        $todayCalories = MealPlan::where('user_id', $user->id)
            ->whereDate('date', today())
            ->with('recipe')
            ->get()
            ->sum(fn($plan) => $plan->recipe->calories ?? 0);

        // Saved Recipes Count
        $savedCount = Favorite::where('user_id', $user->id)->count();

        // Recipes Generated This Week (based on created_at and user’s session or tracking)
        $generatedCount = Recipe::where('created_at', '>=', now()->startOfWeek())->count();

        $weeklyPlans = MealPlan::where('user_id', Auth::id())
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        // Today's Meal Plan
        $todaysPlans = MealPlan::where('user_id', $user->id)
            ->whereDate('date', today())
            ->with('recipe')
            ->get();

        // Recently Saved Recipes
        $recentFavorites = Favorite::where('user_id', $user->id)
            ->latest()
            ->with('recipe')
            ->take(3)
            ->get()
            ->pluck('recipe');

        // Recipe Recommendations (latest recipes from DB)
        $recommendedRecipes = Recipe::latest()->take(4)->get();

        // AI Cooking Tips — cached per user per day to avoid a Groq call on every dashboard view.
        // Only successful, non-empty results are cached; failures fall through so the next visit retries.
        $apiError = false;
        $cacheKey = "daily_cooking_tips:{$user->id}:" . today()->toDateString();
        $tips = Cache::get($cacheKey);

        if (!is_array($tips) || empty($tips)) {
            $tips = $this->generateDailyCookingTips($apiError);
            if (!$apiError && !empty($tips)) {
                Cache::put($cacheKey, $tips, now()->endOfDay());
            }
        }

        return view('dashboard', compact(
            'bmi',
            'bmiCategory',
            'todayCalories',
            'savedCount',
            'generatedCount',
            'weeklyPlans',
            'todaysPlans',
            'recentFavorites',
            'recommendedRecipes',
            'tips',
            'apiError'
        ));
    }

    protected function generateDailyCookingTips(&$apiError = false)
    {
        $apiKey = config('services.groq.key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
                        'messages' => [
                            ['role' => 'user', 'content' => 'Give 3 simple daily cooking tips. Return only array of strings.'],
                        ],
                    ]);

            $json = $response->json();
            $content = $json['choices'][0]['message']['content'] ?? '[]';
            $tips = json_decode($content, true);

            if (!is_array($tips)) {
                $apiError = true;
                return [];
            }

            return $tips;
        } catch (\Exception $e) {
            Log::error('AI Cooking Tips Error: ' . $e->getMessage());
            $apiError = true;
            return [];
        }
    }
}