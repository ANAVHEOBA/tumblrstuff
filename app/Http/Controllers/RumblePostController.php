<?php

namespace App\Http\Controllers;

use App\Services\RumbleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RumblePostController extends Controller
{
    protected $rumbleService;

    public function __construct(RumbleService $rumbleService)
    {
        $this->rumbleService = $rumbleService;
    }

    public function create()
    {
        return view('rumble.post.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'image' => 'nullable|image|max:10240', // 10MB max
            'video' => 'nullable|file|mimetypes:video/*|max:102400', // 100MB max
        ]);

        $user = auth()->user();

        if (!$user->rumbleToken) {
            return redirect()->route('rumble.connect')->with('error', 'Please connect your Rumble account first.');
        }

        $imagePath = null;
        $videoPath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('rumble/images', 'public');
        }

        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('rumble/videos', 'public');
        }

        try {
            $result = $this->rumbleService->publishPost($user, $validated['content'], $imagePath, $videoPath);
            
            // Clean up stored files after successful upload
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            if ($videoPath) Storage::disk('public')->delete($videoPath);

            return redirect()->route('rumble.post.create')->with('success', 'Post published successfully to Rumble.');
        } catch (\Exception $e) {
            // Clean up stored files if upload fails
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            if ($videoPath) Storage::disk('public')->delete($videoPath);

            return back()->with('error', 'Failed to publish post to Rumble: ' . $e->getMessage())->withInput();
        }
    }
}