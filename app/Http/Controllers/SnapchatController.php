<?php

namespace App\Http\Controllers;

use App\Models\SnapchatToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SnapchatController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.snapchat.client_id');
        $this->clientSecret = config('services.snapchat.client_secret');
        $this->redirectUri = config('services.snapchat.redirect');
    }

    public function connect()
    {
        $url = 'https://accounts.snapchat.com/login/oauth2/authorize';
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'snapchat-marketing-api',
        ]);

        return redirect($url . '?' . $query);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');

        $response = Http::post('https://accounts.snapchat.com/login/oauth2/access_token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            SnapchatToken::updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_at' => now()->addSeconds($data['expires_in']),
                ]
            );

            return redirect()->route('home')->with('success', 'Snapchat account connected successfully!');
        }

        return redirect()->route('home')->with('error', 'Failed to connect Snapchat account.');
    }

    public function disconnect()
    {
        auth()->user()->snapchatToken()->delete();
        return redirect()->route('home')->with('success', 'Snapchat account disconnected successfully!');
    }
}