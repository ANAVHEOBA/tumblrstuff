<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Services\WeChatService;

class ComposeController extends Controller
{
    protected $wechatService;

    public function __construct(WeChatService $wechatService)
    {
        $this->wechatService = $wechatService;
    }

    public function index()
    {
        return view('compose');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4|max:10240',
        ]);

        $user = Auth::user();
        $wechatToken = $user->wechatToken;

        if (!$wechatToken) {
            return redirect()->route('compose')->with('error', 'WeChat account not connected.');
        }

        // Save the post to your database
        $post = new Post();
        $post->user_id = $user->id;
        $post->content = $validated['content'];
        
        if ($request->hasFile('media')) {
            $path = $request->file('media')->store('posts', 'public');
            $post->media_url = $path;
        }

        $post->save();

        // Publish to WeChat, it automatically does at this point
        try {
            $result = $this->wechatService->publishPost($wechatToken->access_token, $post);
            if ($result) {
                return redirect()->route('compose')->with('success', 'Content published successfully to WeChat!');
            } else {
                return redirect()->route('compose')->with('error', 'Failed to publish to WeChat. Please try again.');
            }
        } catch (\Exception $e) {
            return redirect()->route('compose')->with('error', 'Error publishing to WeChat: ' . $e->getMessage());
        }
    }
}