<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MeetupDashboardController extends Controller
{
    public function index()
    {
        return view('meetup.dashboard');
    }

    public function publish(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Optional
            'video' => 'mimetypes:video/avi,video/mpeg,video/quicktime|max:20000', // Optional
        ]);

        $user = auth()->user();
        $accessToken = $user->meetupUser->access_token;

        // Prepare payload data
        $payload = [
            'description' => $request->content, // Assuming 'description' field for Meetup event content
            'name' => 'Your Event Title', // You may need to customize the title
            'time' => now()->addDay()->timestamp * 1000, // Set event time (example: tomorrow)
        ];

        // Handle optional image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('meetup_images', 'public');
            // Add logic to upload the image or include image URL in the request if Meetup allows
            $payload['image_url'] = asset('storage/' . $imagePath); // Assuming Meetup allows a URL
        }

        // Handle optional video upload
        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $videoPath = $video->store('meetup_videos', 'public');
            // Add logic to upload the video or include video URL in the request if Meetup allows
            $payload['video_url'] = asset('storage/' . $videoPath); // Assuming Meetup allows a URL
        }

        // API request to Meetup to publish the event
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post('https://api.meetup.com/your-group-urlname/events', $payload);

        // Handle the response
        if ($response->successful()) {
            return redirect()->back()->with('success', 'Content published successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to publish content. Please try again.');
        }
    }
}
