<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class PostController extends Controller
{
    public function create()
    {
        return view('posts.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'required|string',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'video' => 'mimetypes:video/avi,video/mpeg,video/quicktime|max:20000',
        ]);

        $post = new Post();
        $post->user_id = auth()->id();
        $post->content = $validatedData['content'];

        // Handle Image Upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('post_images', 'public');
            $post->image_path = $imagePath;
        }

        // Handle Video Upload
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('post_videos', 'public');
            $post->video_path = $videoPath;
        }

        $post->save();

        // Publish to Baidu
        $this->publishToBaidu($post);

        return redirect()->route('posts.create')->with('status', 'Post created and published to Baidu!');
    }

    private function publishToBaidu(Post $post)
    {
        $user = $post->user;
        $baiduToken = $user->baiduToken;

        if (!$baiduToken) {
            return; // No Baidu token available, skip publishing
        }

        if ($baiduToken->expires_at->isPast()) {
            // Handle token refresh if the token is expired
            $refreshedToken = $this->refreshBaiduToken($baiduToken);

            if (!$refreshedToken) {
                // If token refresh failed, stop the publishing process
                return;
            }

            // Use the refreshed token
            $baiduToken = $refreshedToken;
        }

        // API request to Baidu to publish the post
        $response = Http::post('https://api.baidu.com/post', [
            'access_token' => $baiduToken->access_token,
            'content' => $post->content,
            // Add image and video data if applicable
            'image' => $post->image_path ? asset('storage/' . $post->image_path) : null,
            'video' => $post->video_path ? asset('storage/' . $post->video_path) : null,
        ]);

        // Handle the API response
        if ($response->successful()) {
            $post->published_to_baidu = true;
            $post->baidu_post_id = $response->json()['post_id'] ?? null; // Adjust based on actual Baidu API response
            $post->save();
        }
    }

    private function refreshBaiduToken($baiduToken)
    {
        // Implement token refresh logic, assuming Baidu uses a refresh token
        $response = Http::post('https://api.baidu.com/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $baiduToken->refresh_token,
            'client_id' => config('services.baidu.client_id'),
            'client_secret' => config('services.baidu.client_secret'),
        ]);

        if ($response->successful()) {
            $newAccessToken = $response->json()['access_token'];
            $newExpiresAt = Carbon::now()->addSeconds($response->json()['expires_in']);

            // Update Baidu token in the database
            $baiduToken->update([
                'access_token' => $newAccessToken,
                'expires_at' => $newExpiresAt,
            ]);

            return $baiduToken;
        }

        return null; // Return null if refresh fails
    }
}
