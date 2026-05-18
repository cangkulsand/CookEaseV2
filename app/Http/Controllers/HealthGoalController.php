<?php

namespace App\Http\Controllers;

use App\Models\Bmi;
use App\Models\HealthGoal;
use App\Services\CalorieCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HealthGoalController extends Controller
{
    // For registration - show form
    public function create()
    {
        return view('health_goals.create');
    }

    // For registration - save form data
    public function store(Request $request)
    {
        $request->validate([
            'goal' => 'required|in:lose_weight,gain_weight,maintain_weight',
        ]);

        $user = Auth::user();

        // Save or update the health goal
        $healthGoal = HealthGoal::updateOrCreate(
            ['user_id' => $user->id],
            ['goal' => $request->goal]
        );

        // Get latest BMI for the user
        $bmi = Bmi::where('user_id', $user->id)->latest()->first();

        // Use the service to update calorie target if BMI exists
        if ($bmi) {
            CalorieCalculator::updateCalorieTarget($bmi, $healthGoal->goal);
        }

        // Redirect to dashboard after successful save
        return redirect()->route('dashboard')->with('status', 'health-goal-set');
    }

    // For profile page - update goal
    public function update(Request $request)
    {
        $request->validate([
            'goal' => 'required|in:lose_weight,gain_weight,maintain_weight',
        ]);

        $user = Auth::user();

        // Update existing goal
        $healthGoal = HealthGoal::where('user_id', $user->id)->first();
        if ($healthGoal) {
            $healthGoal->goal = $request->goal;
            $healthGoal->save();
        } else {
            // fallback in case goal didn't exist
            $healthGoal = HealthGoal::create([
                'user_id' => $user->id,
                'goal' => $request->goal,
            ]);
        }

        // Recalculate calorie target if BMI exists
        $bmi = Bmi::where('user_id', $user->id)->latest()->first();
        if ($bmi) {
            CalorieCalculator::updateCalorieTarget($bmi, $healthGoal->goal);
        }

        return redirect()->route('profile.edit')->with('status', 'health-goal-updated');

    }
}
