<?php

namespace App\Http\Controllers;

use App\Models\RumbleToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RumbleController extends Controller
{
    public function connect()
    {
        $clientId = config('services.rumble.client_id');
        $redirectUri = route('rumble.callback');
        $scope = 'read write'; // Adjust scopes as needed

        $url = "https://rumble.com/oauth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code&scope={$scope}";

        return redirect($url);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('home')->with('error', 'Failed to connect Rumble account.');
        }

        $response = Http::post('https://rumble.com/oauth/token', [
            'client_id' => config('services.rumble.client_id'),
            'client_secret' => config('services.rumble.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('rumble.callback'),
        ]);

        if ($response->failed()) {
            return redirect()->route('home')->with('error', 'Failed to obtain Rumble access token.');
        }

        $data = $response->json();

        RumbleToken::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($data['expires_in']),
            ]
        );

        return redirect()->route('home')->with('success', 'Rumble account connected successfully.');
    }

    public function disconnect()
    {
        auth()->user()->rumbleToken()->delete();
        return redirect()->route('home')->with('success', 'Rumble account disconnected successfully.');
    }
}