<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\SnapchatToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class QPostController extends Controller
{
    public function create()
    {
        return view('qcompose');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'required|string|max:1000',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov|max:32000', // 32MB max
            'channels' => 'required|array',
        ]);

        $post = new Post();
        $post->content = $validatedData['content'];
        $post->user_id = auth()->id();

        if ($request->hasFile('media')) {
            $path = $request->file('media')->store('post_media', 'public');
            $post->media_url = $path;
        }

        $post->save();

        foreach ($validatedData['channels'] as $channel) {
            if ($channel === 'snapchat') {
                $this->publishToSnapchat($post);
            }
            // Add more channels as needed
        }

        return redirect()->route('home')->with('success', 'Post published successfully!');
    }

    private function publishToSnapchat(Post $post)
    {
        $snapchatToken = SnapchatToken::where('user_id', auth()->id())->first();

        if (!$snapchatToken) {
            // Handle the case where the user hasn't connected their Snapchat account
            return;
        }

        // Step 1: Create Media
        $mediaResponse = $this->createSnapchatMedia($snapchatToken);

        if (!$mediaResponse || $mediaResponse['request_status'] !== 'success') {
            // Handle media creation error
            return;
        }

        $mediaId = $mediaResponse['media'][0]['media']['id'];

        // Step 2: Upload Media
        $uploadSuccess = $this->uploadMediaToSnapchat($snapchatToken, $mediaId, $post->media_url);

        if (!$uploadSuccess) {
            // Handle media upload error
            return;
        }

        // Step 3: Create Creative (simplified, you might need to adjust based on your needs)
        $creativeResponse = $this->createSnapchatCreative($snapchatToken, $mediaId, $post->content);

        if (!$creativeResponse || $creativeResponse['request_status'] !== 'success') {
            // Handle creative creation error
            return;
        }

        // You might want to store the Snapchat media ID and creative ID in your database
        $post->snapchat_media_id = $mediaId;
        $post->snapchat_creative_id = $creativeResponse['creative'][0]['creative']['id'];
        $post->save();
    }

    private function createSnapchatMedia($snapchatToken)
    {
        $response = Http::withToken($snapchatToken->access_token)
            ->post("https://adsapi.snapchat.com/v1/adaccounts/{$snapchatToken->ad_account_id}/media", [
                'media' => [
                    [
                        'name' => 'Post Media ' . now()->timestamp,
                        'type' => 'VIDEO', // or 'IMAGE' depending on your needs
                        'ad_account_id' => $snapchatToken->ad_account_id,
                    ]
                ]
            ]);

        return $response->json();
    }

    private function uploadMediaToSnapchat($snapchatToken, $mediaId, $mediaUrl)
    {
        $filePath = Storage::disk('public')->path($mediaUrl);
        $fileSize = filesize($filePath);

        if ($fileSize > 32000000) { // 32MB
            return $this->uploadLargeMediaToSnapchat($snapchatToken, $mediaId, $filePath, $fileSize);
        }

        $response = Http::withToken($snapchatToken->access_token)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post("https://adsapi.snapchat.com/v1/media/{$mediaId}/upload");

        return $response->successful();
    }

    private function uploadLargeMediaToSnapchat($snapchatToken, $mediaId, $filePath, $fileSize)
    {
        // Step 1: Initialize chunked upload
        $initResponse = Http::withToken($snapchatToken->access_token)
            ->post("https://adsapi.snapchat.com/v1/media/{$mediaId}/multipart-upload-v2?action=INIT", [
                'file_name' => basename($filePath),
                'file_size' => $fileSize,
                'number_of_parts' => ceil($fileSize / 2000000), // 2MB chunks
            ]);

        if (!$initResponse->successful()) {
            return false;
        }

        $uploadId = $initResponse['upload_id'];
        $addPath = $initResponse['add_path'];
        $finalizePath = $initResponse['finalize_path'];

        // Step 2: Upload chunks
        $handle = fopen($filePath, 'rb');
        $partNumber = 1;

        while (!feof($handle)) {
            $chunk = fread($handle, 2000000); // 2MB chunks
            $response = Http::withToken($snapchatToken->access_token)
                ->attach('file', $chunk, "part_{$partNumber}")
                ->post("https://adsapi.snapchat.com{$addPath}", [
                    'upload_id' => $uploadId,
                    'part_number' => $partNumber,
                ]);

            if (!$response->successful()) {
                fclose($handle);
                return false;
            }

            $partNumber++;
        }

        fclose($handle);

        // Step 3: Finalize upload
        $finalizeResponse = Http::withToken($snapchatToken->access_token)
            ->post("https://adsapi.snapchat.com{$finalizePath}", [
                'upload_id' => $uploadId,
            ]);

        return $finalizeResponse->successful();
    }

    private function createSnapchatCreative($snapchatToken, $mediaId, $content)
    {
        $response = Http::withToken($snapchatToken->access_token)
            ->post("https://adsapi.snapchat.com/v1/adaccounts/{$snapchatToken->ad_account_id}/creatives", [
                'creative' => [
                    [
                        'name' => 'Post Creative ' . now()->timestamp,
                        'type' => 'SNAP_AD',
                        'ad_account_id' => $snapchatToken->ad_account_id,
                        'call_to_action' => 'WATCH_VIDEO',
                        'top_snap_media_id' => $mediaId,
                        'brand_name' => 'Your Brand Name',
                        'headline' => substr($content, 0, 34), // Snapchat headline limit
                    ]
                ]
            ]);

        return $response->json();
    }
}