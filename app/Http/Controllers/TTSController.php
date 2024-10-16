<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TTSController extends Controller
{
    public function convertTextToSpeech(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'text' => 'required|string',
            'language' => 'required|string', // Language format like 'en', 'fr', 'es'
        ]);

        // Get the text and language from the request
        $text = $validated['text'];   // No need to escape twice
        $language = $validated['language'];

        // Define the output file path
        $file_name = 'speech_' . time() . '.mp3';
        $output_file = storage_path("app/public/$file_name");

        // Path to your Python script
        $script_path = base_path('scripts/tts_convert.py');

        // Define the command to run the Python script
        $command = [
            'python3', $script_path, $text, $language, $output_file
        ];

        // Execute the Python script
        $process = new Process($command);
        $process->setTimeout(300);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            return response()->json(['error' => 'Text-to-Speech conversion failed: ' . $exception->getMessage()], 500);
        }

        // Return the audio file as a download if successful
        if (file_exists($output_file)) {
            return response()->download($output_file)->deleteFileAfterSend(true);
        }

        return response()->json(['error' => 'Audio file could not be generated.'], 500);
    }
}
