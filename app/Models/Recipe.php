<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'instructions',
        'ingredients',
        'duration',
        'difficulty',
        'servings',
        'calories',
        'image',
        'grocery_lists',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'grocery_lists' => 'array',
    ];

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function mealPlans()
    {
        return $this->hasMany(MealPlan::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
