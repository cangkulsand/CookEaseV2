<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Models\User;
use App\Notifications\DailyMealPlanReminder;
use Carbon\Carbon;

class NotificationController extends Controller
{
    // Method to send notifications for today's meal plans
    public function sendTodayMealPlanNotifications()
    {
        $today = Carbon::today()->toDateString();

        $users = User::all();

        foreach ($users as $user) {
            $mealPlans = MealPlan::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->get();

            foreach ($mealPlans as $mealPlan) {
                $user->notify(new DailyMealPlanReminder($mealPlan->meal_type, $user->name));
            }
        }

        return response()->json(['message' => 'Meal plan notifications sent.']);
    }
}
