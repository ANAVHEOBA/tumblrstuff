<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class RumbleService
{
    public function publishPost(User $user, string $content, ?string $imagePath = null, ?string $videoPath = null)
    {
        $token = $user->rumbleToken;

        if (!$token) {
            throw new \Exception('Rumble account not connected.');
        }

        $response = Http::withToken($token->access_token)
            ->attach('file', $videoPath ? Storage::disk('public')->get($videoPath) : null)
            ->post('https://rumble.com/api/v0/Media.Upload', [
                'title' => substr($content, 0, 100), 
                'description' => $content,
                // if you want to add anything, more, try to do it here my boss
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to publish post to Rumble: ' . $response->body());
        }

        $mediaId = $response->json('id');

        // If there's an image, upload it separately, my boss
        if ($imagePath) {
            $thumbnailResponse = Http::withToken($token->access_token)
                ->attach('file', Storage::disk('public')->get($imagePath))
                ->post("https://rumble.com/api/v0/Media.UpdateThumbnail", [
                    'media_id' => $mediaId,
                ]);

            if ($thumbnailResponse->failed()) {
                throw new \Exception('Failed to upload thumbnail to Rumble: ' . $thumbnailResponse->body());
            }
        }

        return $response->json();
    }
}