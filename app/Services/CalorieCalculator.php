<?php

namespace App\Services;

use App\Models\Bmi;

class CalorieCalculator
{
    private const ACTIVITY_MULTIPLIER = 1.5;
    private const WEIGHT_CHANGE_KCAL = 500;

    /**
     * Pure calculation only.
     * No database interaction.
     */
    public static function calculate(Bmi $bmi, string $goal = 'maintain_weight'): int
    {
        $bmr = self::calculateBmr($bmi);
        $tdee = $bmr * self::ACTIVITY_MULTIPLIER;

        $calories = match ($goal) {
            'lose_weight' => $tdee - self::WEIGHT_CHANGE_KCAL,
            'gain_weight' => $tdee + self::WEIGHT_CHANGE_KCAL,
            default => $tdee,
        };

        return max(0, (int) round($calories));
    }

    private static function calculateBmr(Bmi $bmi): float
    {
        $isMale = strtolower($bmi->gender) === 'male';

        return (10 * $bmi->weight)
            + (6.25 * $bmi->height)
            - (5 * $bmi->age)
            + ($isMale ? 5 : -161);
    }
}
