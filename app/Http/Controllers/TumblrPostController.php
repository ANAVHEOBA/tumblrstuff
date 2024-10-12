<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TumblrPostController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $tumblrToken = $user->oauthTokens()->where('provider', 'tumblr')->first();

        if (!$tumblrToken) {
            return response()->json(['error' => 'Tumblr account not connected'], 400);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:text,photo,video',
            'content' => 'required_if:type,text',
            'file' => 'required_if:type,photo,video|file|max:10240', // 10MB max
            'blog_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $blogName = $request->input('blog_name');
        $postType = $request->input('type');
        $postData = ['type' => $postType];

        switch ($postType) {
            case 'text':
                $postData['body'] = $request->input('content');
                break;
            case 'photo':
            case 'video':
                $file = $request->file('file');
                $postData['data64'] = base64_encode(file_get_contents($file->path()));
                break;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tumblrToken->access_token,
        ])->post("https://api.tumblr.com/v2/blog/{$blogName}/post", $postData);

        if ($response->successful()) {
            return response()->json(['message' => 'Post created successfully', 'post' => $response->json()]);
        }

        Log::error('Error creating Tumblr post: ' . $response->body());
        return response()->json(['error' => 'Failed to create post', 'details' => $response->json()], 400);
    }
}