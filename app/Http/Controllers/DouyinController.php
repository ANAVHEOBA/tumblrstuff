<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\OauthToken;
use Carbon\Carbon;

class DouyinController extends Controller
{
    private $clientKey;
    private $clientSecret;
    private $redirectUri;

    public function __construct()
    {
        $this->clientKey = config('services.douyin.client_key');
        $this->clientSecret = config('services.douyin.client_secret');
        $this->redirectUri = config('services.douyin.redirect');
    }

    public function connect()
    {
        $scope = 'user_info,video.create'; // try to add more scopes if needed my boss
        $url = "https://open.douyin.com/platform/oauth/connect?client_key={$this->clientKey}&response_type=code&scope={$scope}&redirect_uri={$this->redirectUri}";
        return redirect($url);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        
        $response = Http::post('https://open.douyin.com/oauth/access_token/', [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        $data = $response->json();

        if (isset($data['data']['access_token'])) {
            $user = auth()->user();
            
            OauthToken::updateOrCreate(
                ['user_id' => $user->id, 'provider' => 'douyin'],
                [
                    'access_token' => $data['data']['access_token'],
                    'refresh_token' => $data['data']['refresh_token'],
                    'expires_at' => Carbon::now()->addSeconds($data['data']['expires_in']),
                ]
            );

            return redirect('/compose')->with('success', 'Douyin account connected successfully!');
        }

        return redirect('/')->with('error', 'Failed to connect Douyin account.');
    }

    public function compose()
    {
        return view('compose');
    }

    public function publish(Request $request)
    {
        $user = auth()->user();
        $token = $user->douyinToken();

        if (!$token || $token->isExpired()) {
            return redirect('/douyin/connect')->with('error', 'Please reconnect your Douyin account.');
        }

        $accessToken = $token->access_token;

        // Handle file upload
        $video = $request->file('video');
        $videoPath = $video->store('temp');

        // Upload video to Douyin
        $response = Http::attach(
            'video', file_get_contents(storage_path('app/' . $videoPath)), 'video.mp4'
        )->post("https://open.douyin.com/video/upload/?access_token={$accessToken}", [
            'text' => $request->input('text'),
        ]);

        // Delete temporary file
        unlink(storage_path('app/' . $videoPath));

        $data = $response->json();

        if (isset($data['data']['item_id'])) {
            return redirect('/compose')->with('success', 'Video published successfully!');
        }

        return redirect('/compose')->with('error', 'Failed to publish video.');
    }
}