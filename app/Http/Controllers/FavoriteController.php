<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function saved()
    {
        $favorites = Favorite::with('recipe')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(9);

        return view('saved-recipes', compact('favorites'));
    }
}
