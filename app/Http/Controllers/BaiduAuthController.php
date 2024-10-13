<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BaiduToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class BaiduAuthController extends Controller
{
    public function redirect()
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.baidu.client_id'),
            'redirect_uri' => config('services.baidu.redirect'),
            'scope' => 'basic',
        ]);

        return redirect('https://openapi.baidu.com/oauth/2.0/authorize?' . $query);
    }

    public function callback(Request $request)
    {
        $response = Http::post('https://openapi.baidu.com/oauth/2.0/token', [
            'grant_type' => 'authorization_code',
            'code' => $request->code,
            'client_id' => config('services.baidu.client_id'),
            'client_secret' => config('services.baidu.client_secret'),
            'redirect_uri' => config('services.baidu.redirect'),
        ]);

        $tokenData = $response->json();

        if (!isset($tokenData['access_token'])) {
            return redirect('/login')->with('error', 'Failed to authenticate with Baidu.');
        }

        $userInfoResponse = Http::get('https://openapi.baidu.com/rest/2.0/passport/users/getInfo', [
            'access_token' => $tokenData['access_token'],
        ]);

        $userInfo = $userInfoResponse->json();

        $user = User::updateOrCreate(
            ['baidu_id' => $userInfo['userid']],
            [
                'name' => $userInfo['username'],
                'email' => $userInfo['userid'] . '@baidu.com', // Baidu doesn't provide email, so we create a dummy one
            ]
        );

        BaiduToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]
        );

        Auth::login($user);

        return redirect('/home')->with('status', 'Successfully logged in with Baidu!');
    }
}