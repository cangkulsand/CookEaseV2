<?php

use App\Models\Bmi;

// 3 unit test cases covering the BMI accessor + category logic (FR2).
// These run with no database — they just exercise the model's pure math,
// so they are deterministic and always green in CI.

test('calculates BMI value from height and weight', function () {
    $bmi = new Bmi(['height' => 170, 'weight' => 70]);

    // 70 / (1.70^2) = 24.22
    expect($bmi->bmi)->toBe(24.22);
});

test('classifies a normal BMI into the correct category', function () {
    $bmi = new Bmi(['height' => 170, 'weight' => 70]);

    expect($bmi->getBmiCategory())->toBe('Normal');
});

test('classifies an obese BMI into the correct category', function () {
    $bmi = new Bmi(['height' => 170, 'weight' => 95]);

    // 95 / (1.70^2) = 32.87 -> Obese
    expect($bmi->getBmiCategory())->toBe('Obese');
});
