<?php

namespace App\Http\Controllers;

use App\Services\WeiboOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class WeiboController extends Controller
{
    protected $weiboOAuth;

    public function __construct(WeiboOAuth $weiboOAuth)
    {
        $this->weiboOAuth = $weiboOAuth;
        $this->middleware('auth');
    }

    public function connect()
    {
        $requestToken = $this->weiboOAuth->getRequestToken();
        Session::put('request_token', $requestToken['oauth_token']);
        Session::put('request_token_secret', $requestToken['oauth_token_secret']);

        return redirect($this->weiboOAuth->getAuthorizeUrl($requestToken['oauth_token']));
    }

    public function callback(Request $request)
    {
        $oauthToken = $request->input('oauth_token');
        $oauthVerifier = $request->input('oauth_verifier');

        if ($oauthToken !== Session::get('request_token')) {
            return response('Invalid OAuth request', 400);
        }

        $accessToken = $this->weiboOAuth->getAccessToken($oauthToken, $oauthVerifier);

        // Save the tokens to the database
        auth()->user()->weiboToken()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'oauth_token' => $accessToken['oauth_token'],
                'oauth_token_secret' => $accessToken['oauth_token_secret']
            ]
        );

        return redirect()->route('compose');
    }

    public function compose()
    {
        $weiboToken = auth()->user()->weiboToken;

        if (!$weiboToken) {
            return redirect()->route('weibo.connect')->with('error', 'Please connect your Weibo account first.');
        }

        return view('compose');
    }

    public function publish(Request $request)
    {
        $weiboToken = auth()->user()->weiboToken;

        if (!$weiboToken) {
            return redirect()->route('weibo.connect')->with('error', 'Please connect your Weibo account first.');
        }

        $request->validate([
            'content' => 'required|max:140',
            'media' => 'nullable|file|mimes:jpeg,png,gif,mp4|max:5120', // 5MB max
        ]);

        $params = [
            'status' => $request->input('content'),
            'oauth_token' => $weiboToken->oauth_token,
        ];

        // If media is present, upload it first
        if ($request->hasFile('media')) {
            $media = $request->file('media');
            $mediaUrl = $this->uploadMedia($media, $weiboToken);
            if ($mediaUrl) {
                $params['pic'] = $mediaUrl;
            }
        }

        $url = 'https://api.weibo.com/2/statuses/update.json';
        $baseString = $this->weiboOAuth->generateBaseString('POST', $url, $params);
        $signature = $this->weiboOAuth->generateSignature($baseString, $weiboToken->oauth_token_secret);

        $response = Http::withHeaders([
            'Authorization' => $this->weiboOAuth->buildAuthHeader($params, $signature)
        ])->post($url, $params);

        if ($response->successful()) {
            return redirect()->route('compose')->with('success', 'Your post has been published to Weibo!');
        } else {
            return redirect()->route('compose')->with('error', 'Failed to publish your post. Please try again.');
        }
    }

    protected function uploadMedia($media, $weiboToken)
    {
        $url = 'https://upload.api.weibo.com/2/statuses/upload.json';
        $params = [
            'oauth_token' => $weiboToken->oauth_token,
        ];

        $baseString = $this->weiboOAuth->generateBaseString('POST', $url, $params);
        $signature = $this->weiboOAuth->generateSignature($baseString, $weiboToken->oauth_token_secret);

        $response = Http::withHeaders([
            'Authorization' => $this->weiboOAuth->buildAuthHeader($params, $signature)
        ])->attach(
            'pic', file_get_contents($media->getRealPath()), $media->getClientOriginalName()
        )->post($url, $params);

        if ($response->successful()) {
            $result = $response->json();
            return $result['pic_id'] ?? null;
        }

        return null;
    }
}