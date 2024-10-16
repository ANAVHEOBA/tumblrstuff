<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TTSController extends Controller
{
    public function convertTextToSpeech(Request $request)
    {
        
        $validated = $request->validate([
            'text' => 'required|string',
            'language' => 'required|string', 
        ]);

        
        $text = $validated['text'];   
        $language = $validated['language'];

        
        $file_name = 'speech_' . time() . '.mp3';
        $output_file = storage_path("app/public/$file_name");

        
        $script_path = base_path('scripts/tts_convert.py');

        
        $command = [
            'python3', $script_path, $text, $language, $output_file
        ];

       
        $process = new Process($command);
        $process->setTimeout(300);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            return response()->json(['error' => 'Text-to-Speech conversion failed: ' . $exception->getMessage()], 500);
        }

        
        if (file_exists($output_file)) {
            return response()->download($output_file)->deleteFileAfterSend(true);
        }

        return response()->json(['error' => 'Audio file could not be generated.'], 500);
    }
}
