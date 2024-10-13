<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('post_images', 'public');
            $post->image_path = $imagePath;
        }

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

        if (!$baiduToken || $baiduToken->expires_at->isPast()) {
            // Handle token refresh or re-authentication
            return;
        }

        // This is a placeholder. You'll need to implement the actual API call to Baidu
        $response = Http::post('https://api.baidu.com/post', [
            'access_token' => $baiduToken->access_token,
            'content' => $post->content,
            // Add image and video data as needed
        ]);

        if ($response->successful()) {
            $post->published_to_baidu = true;
            $post->baidu_post_id = $response->json()['post_id']; // Adjust based on actual Baidu API response
            $post->save();
        }
    }
}