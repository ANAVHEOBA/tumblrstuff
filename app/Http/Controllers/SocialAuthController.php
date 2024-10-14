<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
{
    $socialUser = Socialite::driver($provider)->user();

    $user = User::where('email', $socialUser->getEmail())->first();

    if (!$user) {
        $user = User::create([
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'password' => bcrypt(str_random(16)),
        ]);
    }

    $socialAccount = SocialAccount::updateOrCreate(
        [
            'user_id' => $user->id,
            'provider_name' => $provider,
            'provider_id' => $socialUser->getId(),
        ],
        [
            'access_token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken,
            'expires_at' => $socialUser->expiresIn ? now()->addSeconds($socialUser->expiresIn) : null,
        ]
    );

    auth()->login($user);

    return redirect()->route('daily-post.create');
}

}