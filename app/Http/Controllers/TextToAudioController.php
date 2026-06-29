<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\GeneratedAudio;

class TextToAudioController extends Controller
{
    public function index()
    {
        $audios = GeneratedAudio::latest()->get();
        return view('text-to-audio', compact('audios'));
    }

    public function questionLibrary()
    {
        $questions = \DB::connection('questions_db')
            ->table('questions')
            ->whereIn('type', [1, 2])
            ->orderBy('id', 'desc')
            ->paginate(15);
            
        $questionIds = $questions->pluck('id')->toArray();
        
        $answers = \DB::connection('questions_db')
            ->table('question_ans')
            ->whereIn('question_id', $questionIds)
            ->get()
            ->groupBy('question_id');

        return view('question-library', compact('questions', 'answers'));
    }

    public function synthesize(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'text' => 'required|string|max:2000',
        ]);

        $payload = [
            "text" => $request->text,
            "references" => [],
            "reference_id" => null,
            "format" => "wav",
            "latency" => "normal",
            "max_new_tokens" => 1024,
            "chunk_length" => 300,
            "top_p" => 0.8,
            "repetition_penalty" => 1.1,
            "temperature" => 0.8,
            "streaming" => false,
            "use_memory_cache" => "off",
            "seed" => null,
        ];

        $response = Http::timeout(600)->post('http://127.0.0.1:8080/v1/tts', $payload);

        if ($response->successful()) {
            $audioContent = $response->body();
            
            $filename = 'audio_' . time() . '_' . uniqid() . '.wav';
            Storage::disk('public')->put('audios/' . $filename, $audioContent);
            
            $audioRecord = GeneratedAudio::create([
                'text' => $request->text,
                'file_path' => 'audios/' . $filename,
            ]);

            return response()->json([
                'success' => true,
                'url' => asset('storage/' . $audioRecord->file_path),
                'text' => $audioRecord->text,
                'id' => $audioRecord->id,
                'created_at' => $audioRecord->created_at->diffForHumans(),
            ]);
        }

        return response()->json(['error' => 'Audio generation failed. Check Python server logs.'], 500);
    }

    public function stream(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'text' => 'required|string|max:2000',
            'model_type' => 'nullable|string|in:fish,piper,melo',
            'voice' => 'nullable|string',
            'speed' => 'nullable|numeric|min:0.5|max:2.0',
            'preprocess_deepseek' => 'nullable|in:true,false,1,0',
        ]);

        $modelType = $request->model_type ?? 'fish';
        $voice = $request->voice ?? 'EN-US';
        $speed = $request->speed ?? 1.0;
        
        $textToProcess = $request->text;
        if ($request->boolean('preprocess_deepseek')) {
            $apiKey = env('DEEPSEEK_API_KEY');
            if ($apiKey) {
                try {
                    $prompt = <<<EOT
You are a medical text preprocessor for a Text-to-Speech engine.
Convert the provided medical MCQ into a naturally spoken, human-like script.

Follow these rules strictly:
1. Replace (True) with ". Correct." and (False) with ". Incorrect."
2. Replace "/" with " or ".
3. Expand ALL CAPS medical abbreviations into spaced letters (e.g., SAH -> S A H, SSRI -> S S R I).
4. Convert complex medical terms into smooth, conversational phonetic spellings so a TTS engine reads them like a doctor speaking naturally. Examples:
   - subarachnoid -> sub-uh-rak-noid
   - hemorrhage -> hem-rij (or heh-mor-ij, but keep it smooth)
   - saccular -> sak-yuh-ler
   - berry -> beh-ree
   - aneurysm -> an-yuh-rizm
   - tricyclics -> trye-sy-kliks
   - lamotrigine -> luh-moh-tri-jeen
   - benzodiazepines -> ben-zoh-dye-az-uh-peens
   - serotonin -> seh-ruh-toe-nin
   - reuptake -> ree-up-take
   - inhibitor -> in-hib-i-tor
5. Replace option labels (a), b), c), d)) with "Option A", "Option B", "Option C", "Option D" for clear spoken distinction.
6. Do NOT change or fix the medical facts/accuracy.
7. Return ONLY the final processed plain text. No explanations, no markdown.
EOT;
                    $response = Http::withToken($apiKey)->timeout(30)->post('https://api.deepseek.com/v1/chat/completions', [
                        'model' => 'deepseek-chat',
                        'messages' => [
                            ['role' => 'system', 'content' => $prompt],
                            ['role' => 'user', 'content' => $textToProcess]
                        ]
                    ]);
                    if ($response->successful()) {
                        $textToProcess = $response->json('choices.0.message.content') ?? $textToProcess;
                    } else {
                        \Illuminate\Support\Facades\Log::error("DeepSeek API failed: " . $response->body());
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("DeepSeek Exception: " . $e->getMessage());
                }
            } else {
                \Illuminate\Support\Facades\Log::warning("DEEPSEEK_API_KEY is not set in .env");
            }
        }

        $payload = [
            "text" => $textToProcess,
            "references" => [],
            "reference_id" => null,
            "format" => "wav",
            "latency" => "normal",
            "max_new_tokens" => 1024,
            "chunk_length" => 100, // Reduced from 300 to stream earlier
            "top_p" => 0.8,
            "repetition_penalty" => 1.1,
            "temperature" => 0.8,
            "streaming" => true,
            "use_memory_cache" => "off",
            "seed" => null,
            "voice" => $voice,
            "speed" => (float)$speed,
        ];

        // Prepare file for saving
        $localFileName = 'audio_' . time() . '_' . uniqid() . '.wav';
        $localFilePath = storage_path('app/public/audios/' . $localFileName);

        // Ensure directory exists
        if (!file_exists(dirname($localFilePath))) {
            mkdir(dirname($localFilePath), 0755, true);
        }

        $audioRecord = GeneratedAudio::create([
            'text' => $textToProcess,
            'file_path' => 'audios/' . $localFileName,
        ]);

        if ($modelType === 'piper') {
            $piperBin = '/Users/abid/Development/My_personal/Text-to-audio/piper-tts/.venv/bin/piper';
            $piperModel = '/Users/abid/Development/My_personal/Text-to-audio/piper-tts/en_US-lessac-high.onnx';
            
            if ($request->voice) {
                $safeVoice = basename($request->voice);
                $customModel = '/Users/abid/Development/My_personal/Text-to-audio/piper-tts/' . $safeVoice . '.onnx';
                if (file_exists($customModel)) {
                    $piperModel = $customModel;
                }
            }
            
            // Calculate piper length scale (higher = slower). Formula: 1 / speed. Example: speed 0.8 = length_scale 1.25
            $lengthScale = 1.0;
            if ($speed > 0) {
                $lengthScale = 1.0 / $speed;
            }
            // Add a bit more gap between sentences when slower
            $sentenceSilence = 0.2;
            if ($speed < 1.0) {
                $sentenceSilence = 0.4;
            }
            
            $tmpTextFile = tempnam(sys_get_temp_dir(), 'piper_txt');
            file_put_contents($tmpTextFile, $textToProcess);
            
            $cmd = escapeshellcmd($piperBin) . ' --model ' . escapeshellarg($piperModel) . ' --length_scale ' . escapeshellarg($lengthScale) . ' --sentence_silence ' . escapeshellarg($sentenceSilence) . ' --output_file ' . escapeshellarg($localFilePath) . ' < ' . escapeshellarg($tmpTextFile) . ' 2>&1';
            $output = shell_exec($cmd);
            \Illuminate\Support\Facades\Log::info("Piper Output: " . $output);
            unlink($tmpTextFile);
            
            return response()->file($localFilePath, [
                'Content-Type' => 'audio/wav',
                'Cache-Control' => 'no-cache',
            ]);
        }

        return response()->stream(function () use ($payload, $localFilePath, $modelType) {
            $endpoint = 'http://127.0.0.1:8080/v1/tts'; // default fish speech
            if ($modelType === 'melo') {
                $endpoint = 'http://127.0.0.1:8082/v1/tts'; // MeloTTS server
            }

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3600); // 1 hour timeout for slow generation
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            
            $fileHandle = fopen($localFilePath, 'w');
            
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use ($fileHandle) {
                // Write to local file
                fwrite($fileHandle, $chunk);
                
                // Stream to browser
                echo $chunk;
                flush();
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                
                return strlen($chunk);
            });
            
            curl_exec($ch);
            curl_close($ch);
            fclose($fileHandle);
            
        }, 200, [
            'Content-Type' => 'audio/wav',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // Disable Nginx proxy buffering
            'X-Audio-URL' => asset('storage/' . $audioRecord->file_path),
            'X-Audio-Text' => urlencode($audioRecord->text),
            'X-Audio-Date' => $audioRecord->created_at->diffForHumans()
        ]);
    }

    public function latest()
    {
        $audioRecord = GeneratedAudio::latest()->first();
        if ($audioRecord) {
            return response()->json([
                'success' => true,
                'url' => asset('storage/' . $audioRecord->file_path),
                'text' => $audioRecord->text,
                'id' => $audioRecord->id,
                'created_at' => $audioRecord->created_at->diffForHumans(),
            ]);
        }
        return response()->json(['error' => 'Not found'], 404);
    }
}
