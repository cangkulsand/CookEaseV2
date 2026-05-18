<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $socialUser = Socialite::driver('google')->user();
        $user = $this->loginOrRegisterUser($socialUser);

        return $user->bmi ? redirect()->route('dashboard') : redirect()->route('bmi.form');
    }

    protected function loginOrRegisterUser($socialUser)
    {
        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            ['name' => $socialUser->getName(),
            ]
        );
        Auth::login($user);

        return $user->load('bmi');
    }
}
