<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class DailyPostController extends Controller
{
    public function create()
    {
        return view('daily-post.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'media' => 'nullable|file|mimes:jpeg,png,mp4,mov',
        ]);

        $user = auth()->user();
        $socialAccount = $user->socialAccounts()->where('provider_name', 'dailymotion')->first();

        if (!$socialAccount) {
            return back()->with('error', 'Dailymotion account not connected.');
        }

        $client = new Client();

        // First, upload the media if present
        $mediaUrl = null;
        if ($request->hasFile('media')) {
            $mediaFile = $request->file('media');
            $uploadUrl = $this->getUploadUrl($client, $socialAccount->access_token);
            $mediaUrl = $this->uploadMedia($client, $uploadUrl, $mediaFile);
        }

        // Then, publish the content
        $response = $client->post('https://api.dailymotion.com/me/videos', [
            'headers' => [
                'Authorization' => 'Bearer ' . $socialAccount->access_token,
            ],
            'form_params' => [
                'title' => substr($request->content, 0, 255),
                'description' => $request->content,
                'channel' => 'news',
                'published' => true,
                'url' => $mediaUrl,
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        return back()->with('success', 'Content published successfully to Dailymotion.');
    }

    private function getUploadUrl($client, $accessToken)
    {
        $response = $client->get('https://api.dailymotion.com/file/upload', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        $result = json_decode($response->getBody(), true);
        return $result['upload_url'];
    }

    private function uploadMedia($client, $uploadUrl, $mediaFile)
    {
        $response = $client->post($uploadUrl, [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($mediaFile->getPathname(), 'r'),
                    'filename' => $mediaFile->getClientOriginalName(),
                ],
            ],
        ]);

        $result = json_decode($response->getBody(), true);
        return $result['url'];
    }
}