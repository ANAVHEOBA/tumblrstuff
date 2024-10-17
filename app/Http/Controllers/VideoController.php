<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class VideoController extends Controller
{
    public function convertSpeechToVideo(Request $request)
    {
        $validated = $request->validate([
            'speech_file' => 'required|string', // This will be the filename generated from TTS
            'background_image' => 'nullable|file|mimes:jpeg,jpg,png',
        ]);

        // Get the path to the speech file
        $speech_filename = $validated['speech_file'];
        $speech_path = storage_path("app/public/{$speech_filename}");

        // Verify the speech file exists
        if (!file_exists($speech_path)) {
            return response()->json([
                'error' => 'Speech file not found. Please generate the speech file first using the text-to-speech endpoint.'
            ], 404);
        }

        // Generate output video filename
        $video_filename = 'video_' . time() . '.mp4';
        $output_path = storage_path("app/public/{$video_filename}");

        // Handle background image if provided
        $background_path = null;
        if ($request->hasFile('background_image')) {
            $background_path = $request->file('background_image')->store('temp');
            $background_path = storage_path("app/{$background_path}");
        }

        // Prepare the command
        $script_path = base_path('scripts/video_creator.py');
        $command = array_filter([
            'python3',
            $script_path,
            $speech_path,
            $output_path,
            $background_path
        ]);

        // Execute the conversion process
        $process = new Process($command);
        $process->setTimeout(600); // 10 minutes timeout

        try {
            $process->mustRun();

            // Clean up temporary files
            if ($background_path && file_exists($background_path)) {
                unlink($background_path);
            }

            // Return the video file
            if (file_exists($output_path)) {
                // Delete the speech file after successful video creation
                unlink($speech_path);
                
                // Return video file download response
                return response()->download($output_path)->deleteFileAfterSend(true);
            }

            return response()->json(['error' => 'Video file could not be generated.'], 500);

        } catch (ProcessFailedException $exception) {
            // Clean up any temporary files
            if ($background_path && file_exists($background_path)) {
                unlink($background_path);
            }

            return response()->json([
                'error' => 'Video conversion failed: ' . $exception->getMessage()
            ], 500);
        }
    }
}