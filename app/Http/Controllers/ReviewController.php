<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $userId = Auth::id();

        $data = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'rating'    => 'required|integer|min:1|max:5',
            'comment'   => 'required|string|max:1000',
        ]);

        $recipeId = $data['recipe_id'];

        // Check if user saved recipe (required condition)
        if (
            ! Favorite::where('user_id', $userId)
                ->where('recipe_id', $recipeId)
                ->exists()
        ) {
            return back()->with('message', 'Save recipe first before reviewing.');
        }

        // Prevent duplicate review
        if (
            Review::where('user_id', $userId)
                ->where('recipe_id', $recipeId)
                ->exists()
        ) {
            return back()->with('message', 'You already reviewed this recipe.');
        }

        // Create review
        Review::create([
            'user_id'   => $userId,
            'recipe_id' => $recipeId,
            'rating'    => $data['rating'],
            'comment'   => $data['comment'],
        ]);

        return back()->with('message', 'Review submitted.');
    }
}
