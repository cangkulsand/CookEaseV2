<?php

namespace App\Services;

use App\Models\Bmi;

class BmiService
{
    /**
     * Handles persistence only.
     */
    public function updateCalorieTarget(Bmi $bmi, string $goal = 'maintain_weight'): Bmi
    {
        $bmi->calorie_target = CalorieCalculator::calculate($bmi, $goal);
        $bmi->save();

        return $bmi;
    }
}
