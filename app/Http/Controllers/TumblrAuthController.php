<?php

namespace App\Http\Controllers;

use App\Models\OauthToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TumblrAuthController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.tumblr.client_id');
        $this->clientSecret = config('services.tumblr.client_secret');
        $this->redirectUri = config('services.tumblr.redirect');
    }

    public function redirect()
    {
        $state = Str::random(40);
        session(['tumblr_oauth_state' => $state]);
        
        Log::info('Tumblr OAuth Redirect URI: ' . $this->redirectUri);
        Log::info('Generated state: ' . $state);

        $query = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'basic write offline_access',
            'state' => $state,
            'redirect_uri' => $this->redirectUri,
        ]);

        $authUrl = 'https://www.tumblr.com/oauth2/authorize?' . $query;
        Log::info('Full Tumblr Auth URL: ' . $authUrl);
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        Log::info('Callback received. Request data: ' . json_encode($request->all()));
        
        $state = $request->get('state');
        Log::info('Received state: ' . $state);
        Log::info('Stored state: ' . session('tumblr_oauth_state'));
        
        if ($state !== session('tumblr_oauth_state')) {
            Log::error('State mismatch in callback');
            return response('Invalid state', 400);
        }

        session()->forget('tumblr_oauth_state');

        $code = $request->get('code');
        if (!$code) {
            Log::error('No authorization code received from Tumblr');
            if ($request->has('error')) {
                Log::error('Tumblr error: ' . $request->get('error'));
                Log::error('Tumblr error description: ' . $request->get('error_description'));
                return response('Tumblr error: ' . $request->get('error_description'), 400);
            }
            return response('No authorization code received', 400);
        }

        Log::info('Attempting to get token with code: ' . $code);

        $response = Http::asForm()->post('https://api.tumblr.com/v2/oauth2/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
        ]);

        Log::info('Token request response: ' . $response->body());

        if ($response->successful()) {
            $token = $response->json();
            $user = auth()->user();

            OauthToken::updateOrCreate(
                ['user_id' => $user->id, 'provider' => 'tumblr'],
                [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'],
                    'expires_at' => now()->addSeconds($token['expires_in']),
                ]
            );

            $sanctumToken = $user->createToken('tumblr-access');
            return response()->json(['token' => $sanctumToken->plainTextToken]);
        }

        Log::error('Error getting token: ' . $response->body());
        return response('Error getting token: ' . $response->body(), 400);
    }

    public function getUserInfo(Request $request)
    {
        $user = $request->user();
        $tumblrToken = $user->oauthTokens()->where('provider', 'tumblr')->first();

        if (!$tumblrToken) {
            return response('No Tumblr token found', 400);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tumblrToken->access_token,
        ])->get('https://api.tumblr.com/v2/user/info');

        if ($response->successful()) {
            return $response->json();
        }

        return response('Error getting user info: ' . $response->body(), 400);
    }

    public function refreshToken(Request $request)
    {
        $user = $request->user();
        $tumblrToken = $user->oauthTokens()->where('provider', 'tumblr')->first();

        if (!$tumblrToken) {
            return response('No Tumblr token found', 400);
        }

        $response = Http::asForm()->post('https://api.tumblr.com/v2/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tumblrToken->refresh_token,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            $token = $response->json();
            $tumblrToken->update([
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'],
                'expires_at' => now()->addSeconds($token['expires_in']),
            ]);

            return response('Token refreshed successfully');
        }

        return response('Error refreshing token: ' . $response->body(), 400);
    }

    public function checkTumblrConnection(Request $request)
    {
        $user = $request->user();
        $tumblrToken = $user->oauthTokens()->where('provider', 'tumblr')->first();

        if (!$tumblrToken || now()->greaterThan($tumblrToken->expires_at)) {
            return response()->json(['connected' => false]);
        }

        return response()->json(['connected' => true]);
    }
}