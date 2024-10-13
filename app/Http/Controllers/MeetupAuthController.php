<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\MeetupUser;

class MeetupAuthController extends Controller
{
    public function redirectToMeetup()
    {
        $clientId = config('services.meetup.client_id');
        $redirectUri = urlencode(route('auth.meetup.callback'));
        $url = "https://secure.meetup.com/oauth2/authorize?client_id={$clientId}&response_type=code&redirect_uri={$redirectUri}";
        return redirect($url);
    }

    public function handleMeetupCallback(Request $request)
    {
        $code = $request->code;
        $clientId = config('services.meetup.client_id');
        $clientSecret = config('services.meetup.client_secret');
        $redirectUri = route('auth.meetup.callback');

        $response = Http::post('https://secure.meetup.com/oauth2/access', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        $tokenData = $response->json();

        if (isset($tokenData['access_token'])) {
            $userInfo = $this->getMeetupUserInfo($tokenData['access_token']);
            
            $user = MeetupUser::updateOrCreate(
                ['meetup_id' => $userInfo['id']],
                [
                    'name' => $userInfo['name'],
                    'email' => $userInfo['email'],
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'],
                ]
            );

            Auth::login($user);

            return redirect()->route('meetup.dashboard');
        }

        return redirect('/')->with('error', 'Unable to authenticate with Meetup');
    }

    private function getMeetupUserInfo($accessToken)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://api.meetup.com/members/self');

        return $response->json();
    }
}