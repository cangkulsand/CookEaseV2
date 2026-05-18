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
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        // ✅ Check if user has saved the recipe
        $hasSaved = Favorite::where('user_id', Auth::id())
            ->where('recipe_id', $request->recipe_id)
            ->exists();

        if (! $hasSaved) {
            return back()->with('message', 'Please save this recipe first before leaving a review.');
        }

        // ✅ Prevent duplicate review
        $existingReview = Review::where('user_id', Auth::id())
            ->where('recipe_id', $request->recipe_id)
            ->first();

        if ($existingReview) {
            return back()->with('message', 'You already submitted a review for this recipe.');
        }

        // ✅ Create the review
        Review::create([
            'user_id' => Auth::id(),
            'recipe_id' => $request->recipe_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return back()->with('message', 'Review submitted!');
    }
}
