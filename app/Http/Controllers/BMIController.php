<?php

namespace App\Http\Controllers;

use App\Models\Bmi;
use App\Services\CalorieCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BMIController extends Controller
{
    public function showForm()
    {
        return view('bmi.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'age' => 'required|integer',
            'gender' => 'required|in:male,female',
            'height' => 'required|numeric',
            'weight' => 'required|numeric',
        ]);

        $bmi = new Bmi;
        $bmi->user_id = auth::id();
        $bmi->age = $request->age;
        $bmi->gender = $request->gender;
        $bmi->height = $request->height;
        $bmi->weight = $request->weight;
        $bmi->save();

        return redirect()->route('health_goals.create');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $bmi = $user->bmi;

        $request->validate([
            'age' => 'required|integer|min:18|max:120',
            'height' => 'required|numeric|min:50|max:250',
            'weight' => 'required|numeric|min:10|max:500',
        ]);

        $bmi = Bmi::where('user_id', auth::id())->first();
        $bmi->age = $request->age;
        $bmi->height = $request->height;
        $bmi->weight = $request->weight;
        $bmi->bmi_value = round($request->weight / pow($request->height / 100, 2), 2);
        $bmi->save();

        CalorieCalculator::updateCalorieTarget($bmi, optional($user->healthGoal)->goal);

        return redirect()->back()->with('status', 'bmi-updated');

    }
}
