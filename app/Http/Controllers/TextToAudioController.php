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

    public function questionLibrary(Request $request)
    {
        $type = $request->query('type', 'all');

        $query = \DB::connection('questions_db')
            ->table('questions')
            ->orderBy('id', 'desc');

        if ($type === '1') {
            $query->where('type', 1);
        } elseif ($type === '2') {
            $query->where('type', 2);
        } else {
            $query->whereIn('type', [1, 2]);
        }

        $questions = $query->paginate(15)->appends(['type' => $type]);
            
        $questionIds = $questions->pluck('id')->toArray();
        
        $answers = \DB::connection('questions_db')
            ->table('question_ans')
            ->whereIn('question_id', $questionIds)
            ->get()
            ->groupBy('question_id');

        $questionAudios = GeneratedAudio::whereIn('question_id', $questionIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('question_id');

        return view('question-library', compact('questions', 'answers', 'type', 'questionAudios'));
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
            'model_type' => 'nullable|string|in:fish,piper,melo,chattts,styletts2,kokoro',
            'voice' => 'nullable|string',
            'speed' => 'nullable|numeric|min:0.5|max:2.0',
            'diffusion_steps' => 'nullable|integer|min:3|max:20',
            'preprocess_deepseek' => 'nullable|in:true,false,1,0',
            'chattts_speed' => 'nullable|integer',
            'chattts_temp' => 'nullable|numeric',
            'chattts_top_p' => 'nullable|numeric',
            'chattts_top_k' => 'nullable|integer',
        ]);

        $modelType = $request->model_type ?? 'melo';
        $voice = $request->voice;
        if (!$voice) {
            if ($modelType === 'kokoro') $voice = 'af_heart';
            elseif ($modelType === 'styletts2') $voice = 'default';
            elseif ($modelType === 'melo') $voice = 'EN-US';
            elseif ($modelType === 'chattts') $voice = 'random';
            else $voice = 'default';
        }
        $speed = $request->speed ?? 1.0;
        
        $textToProcess = $request->text;
        $questionId = null;
        $deepseekText = null;
        
        // Process question text to ensure DeepSeek gets clean and full information
        if (is_numeric(trim($textToProcess))) {
            $questionId = trim($textToProcess);
            $question = \DB::connection('questions_db')->table('questions')->where('id', $questionId)->first();
            
            if ($question) {
                $answers = \DB::connection('questions_db')->table('question_ans')->where('question_id', $questionId)->get();
                
                $structuredText = "Question: " . html_entity_decode(strip_tags(str_replace("&nbsp;", " ", $question->question_title))) . "\n\nOptions:\n";
                foreach ($answers as $ans) {
                    $isCorrect = "";
                    if ($question->type == 1) {
                        $cAns = strtoupper(trim($ans->correct_ans));
                        $isCorrect = ($cAns === 'T' || $cAns === '1') ? " (True)" : " (False)";
                    }
                    $structuredText .= "(" . strtolower($ans->sl_no) . ") " . html_entity_decode(strip_tags(str_replace("&nbsp;", " ", $ans->answer))) . $isCorrect . "\n";
                }
                
                if (!empty($question->correct_ans) && $question->type != 1) {
                    $structuredText .= "\nCorrect Answer: " . trim($question->correct_ans) . "\n";
                }
                
                if (!empty($question->discussion)) {
                    $structuredText .= "\nExplanation:\n" . html_entity_decode(strip_tags(str_replace("&nbsp;", " ", $question->discussion))) . "\n";
                }
                
                $textToProcess = $structuredText;
            }
        } else {
            $textToProcess = html_entity_decode(strip_tags(str_replace("&nbsp;", " ", $textToProcess)));
        }
        if ($request->boolean('preprocess_deepseek')) {
            $apiKey = env('DEEPSEEK_API_KEY');
            if ($apiKey) {
                try {
                    $shortPause = ". ... ";
                    $longPause = ". ...... ";
                    
                    if ($modelType === 'chattts') {
                        $shortPause = " [uv_break] ";
                        $longPause = " [uv_break] [uv_break] ";
                    } elseif ($modelType === 'kokoro') {
                        $shortPause = "... ";
                        $longPause = ". \n\n ";
                    }

                    $basePrompt = <<<EOT
You are an expert medical text preprocessor for a Text-to-Speech (TTS) engine.
Your task is to rewrite raw medical MCQs so that a TTS engine can read them naturally and with flawless medical pronunciation.

CRITICAL INSTRUCTION: You MUST return the FULL text provided by the user. This includes the Question title, all Options, the Correct Answer (if present), and the Explanation (if present). DO NOT omit or skip the question text or explanation. Your job is ONLY to reformat the text for TTS.

Follow these strict rules:
1. Identify and REMOVE all exam metadata, dates, or department codes from the text (e.g., "Paed. Jan-20", "FCPS Part-1" should be completely deleted).
2. Clean up structural noise like duplicated option letters (e.g., change "(a) a)" or "a)" to "Option A:").
3. Ensure questions sound like questions: Add a Question mark (?) at the end of the question sentence so the TTS adopts an interrogative tone. Then insert a LONG pause ("{$longPause}") before starting Option A. Make it sound like a teacher explaining a quiz.
4. For True/False questions (where options have True/False/✓/✗): 
   - Read the option text, then insert a short pause ("{$shortPause}"), then say "True," or "False,".
   - Then insert a LONG pause ("{$longPause}") before the next option.
   - Format example: "Option A: Bradycardia.{$shortPause}False,{$longPause}Option B: Tremor.{$shortPause}True,{$longPause}"
5. For Single Best Answer (SBA) questions (where there is a "Correct Answer:" at the end):
   - Insert a LONG pause ("{$longPause}") between each option.
   - Format example: "Option A: Bradycardia.{$longPause}Option B: Tremor.{$longPause}"
   - At the very end, clearly announce the correct answer by repeating the option text, e.g., "The correct answer is Option C, Anhydrosis."
6. Do NOT use any markdown formatting, XML tags, or explanations of what you did in your response. Return ONLY the preprocessed text.
7. Expand abbreviations like "Q." or "Q:" at the beginning of a question to "Question:" so the TTS engine reads it properly instead of "Q dot".
8. Expand chemical formulas, medical abbreviations, and units of measurement into full, naturally spoken English words. For example, expand "mmol/L" to "millimoles per liter", "[H]" to "Hydrogen ion concentration", and "[H2CO3]:[HCO3]" to "ratio of carbonic acid to bicarbonate". Ensure it sounds like a human reading a medical text.
EOT;

                    if ($modelType === 'chattts') {
                        $prompt = $basePrompt . "\n\n9. PRONUNCIATION (ChatTTS): Do NOT use hyphens to break words. Tweak the spelling of hard medical terms into simple English words that sound identical (e.g., 'Calcitonin' -> 'Calsitonin'). Use ONLY the [uv_break] tag for pauses as instructed above, do NOT use ellipses (...) for pauses as it causes hallucination.";
                    } elseif ($modelType === 'styletts2') {
                        $prompt = $basePrompt . "\n\n9. PRONUNCIATION (StyleTTS2): Keep complex medical terms in their original, proper spelling. Do NOT use hyphens or phonetic spelling, as StyleTTS2 handles proper spelling best.";
                    } elseif ($modelType === 'kokoro') {
                        $prompt = $basePrompt . "\n\n9. PRONUNCIATION (Kokoro): Keep complex medical terms in their original, proper spelling. Do NOT use hyphens or phonetic spelling, as Kokoro handles proper spelling best.";
                    } else {
                        // Piper / Default
                        $prompt = $basePrompt . "\n\n9. PRONUNCIATION (Piper): Break down complex medical terms into 'Google-style phonetic spelling' separated by hyphens (e.g., 'Hypothyroidism' -> 'hai-pow-thai-roy-di-zm'). Insert commas between words that might sound rushed together.";
                    }
                    $response = Http::withToken($apiKey)->timeout(120)->post('https://api.deepseek.com/v1/chat/completions', [
                        'model' => 'deepseek-chat',
                        'messages' => [
                            ['role' => 'system', 'content' => $prompt],
                            ['role' => 'user', 'content' => $textToProcess]
                        ]
                    ]);
                    if ($response->successful()) {
                        $deepseekText = $response->json('choices.0.message.content');
                        $textToProcess = $deepseekText ?? $textToProcess;
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
            "chattts_speed" => intval($request->input('chattts_speed', 5)),
            "chattts_temp" => floatval($request->input('chattts_temp', 0.3)),
            "chattts_top_p" => floatval($request->input('chattts_top_p', 0.7)),
            "chattts_top_k" => intval($request->input('chattts_top_k', 20)),
        ];
        
        if ($request->has('diffusion_steps')) {
            $payload['diffusion_steps'] = (int) $request->input('diffusion_steps');
        }
        if ($modelType === 'kokoro') {
            if ($request->has('voice2')) {
                $payload['voice2'] = $request->input('voice2');
            }
            if ($request->has('blend_method')) {
                $payload['blend_method'] = $request->input('blend_method');
            }
            if ($request->has('blend_ratio')) {
                $payload['blend_ratio'] = (float) $request->input('blend_ratio');
            }
        }

        // Prepare file for saving
        $localFileName = 'audio_' . time() . '_' . uniqid() . '.wav';
        $localFilePath = storage_path('app/public/audios/' . $localFileName);

        // Ensure directory exists
        if (!file_exists(dirname($localFilePath))) {
            mkdir(dirname($localFilePath), 0755, true);
        }

        $audioRecord = GeneratedAudio::create([
            'question_id' => $questionId,
            'text' => $textToProcess,
            'deepseek_text' => $deepseekText,
            'model_type' => $modelType,
            'voice' => $voice,
            'speed' => $speed,
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
            
            $headers = [
                'Content-Type' => 'audio/wav',
                'Cache-Control' => 'no-cache',
                'X-Generated-Audio-ID' => $audioRecord->id,
                'X-Generated-Deepseek-Text' => base64_encode($deepseekText ?? ''),
                'X-Generated-Text' => base64_encode($textToProcess),
                'X-Audio-Model' => $modelType,
                'X-Audio-Voice' => $voice,
                'X-Audio-Speed' => $speed,
            ];
            
            return response()->file($localFilePath, $headers);
        }

        return response()->stream(function () use ($payload, $localFilePath, $modelType) {
            $endpoint = 'http://127.0.0.1:8080/v1/tts'; // default fish speech
            if ($modelType === 'melo') {
                $endpoint = 'http://127.0.0.1:8082/v1/tts'; // MeloTTS server
            } elseif ($modelType === 'chattts') {
                $endpoint = 'http://127.0.0.1:8083/v1/tts'; // ChatTTS server
            } elseif ($modelType === 'styletts2') {
                $endpoint = 'http://127.0.0.1:8084/v1/tts'; // StyleTTS2 server
            } elseif ($modelType === 'kokoro') {
                $endpoint = 'http://127.0.0.1:8085/v1/tts'; // Kokoro TTS server
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
            'X-Audio-Date' => $audioRecord->created_at->diffForHumans(),
            'X-Generated-Audio-ID' => $audioRecord->id,
            'X-Generated-Deepseek-Text' => base64_encode($deepseekText ?? ''),
            'X-Audio-Model' => $modelType,
            'X-Audio-Voice' => $voice,
            'X-Audio-Speed' => $speed,
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
