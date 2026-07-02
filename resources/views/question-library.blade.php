<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Library - AI Text to Speech</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-200 min-h-screen p-4 md:p-8 font-sans">

    <div class="max-w-7xl mx-auto mb-6 bg-slate-800 rounded-xl p-4 flex gap-4 shadow-xl border border-slate-700">
        <a href="{{ route('audio.index') }}" class="px-4 py-2 rounded-lg bg-slate-700 text-slate-300 font-medium hover:bg-slate-600 hover:text-white transition-colors">
            Manual Input
        </a>
        <a href="{{ route('audio.library') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-500 transition-colors">
            Question Library
        </a>
    </div>

    <div class="max-w-7xl mx-auto bg-slate-800 p-6 rounded-2xl shadow-2xl border border-slate-700">
        <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-[100]">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Question Library
                </h2>
                <p class="text-slate-400 text-sm mt-1">Live from {{ env('DB_QUESTIONS_DATABASE') }}</p>
            </div>
            
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <div class="flex bg-slate-900 rounded-lg p-1 border border-slate-700">
                    <a href="{{ request()->fullUrlWithQuery(['type' => 'all', 'page' => null]) }}" class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors {{ $type === 'all' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200' }}">All Types</a>
                    <a href="{{ request()->fullUrlWithQuery(['type' => '1', 'page' => null]) }}" class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors {{ $type === '1' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200' }}">Type 1</a>
                    <a href="{{ request()->fullUrlWithQuery(['type' => '2', 'page' => null]) }}" class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors {{ $type === '2' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200' }}">Type 2</a>
                </div>
                
                <form method="GET" action="{{ route('audio.library') }}" class="flex" id="filterForm">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="book_id" id="hiddenBookId" value="{{ request('book_id') }}">
                    
                    <div class="relative w-[250px]" id="bookDropdown">
                        <div class="px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-sm text-slate-300 cursor-pointer flex justify-between items-center" onclick="toggleDropdown()">
                            @php
                                $selectedBook = request('book_id') ? $books->firstWhere('id', request('book_id')) : null;
                            @endphp
                            <span id="selectedBookText" class="truncate pr-2">
                                {{ $selectedBook ? $selectedBook->book_name . ' (ID: ' . $selectedBook->id . ')' : 'All Books' }}
                            </span>
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        
                        <div id="dropdownList" class="hidden absolute top-full left-0 w-[300px] mt-1 bg-slate-800 border border-slate-700 rounded-lg shadow-2xl z-50">
                            <div class="p-2 border-b border-slate-700">
                                <input type="text" id="bookSearch" placeholder="Search by name or ID..." class="w-full px-3 py-1.5 bg-slate-900 border border-slate-600 rounded text-sm text-slate-200 focus:outline-none focus:border-indigo-500" onkeyup="filterBooks()">
                            </div>
                            <div class="max-h-60 overflow-y-auto" id="bookOptions">
                                <a href="javascript:void(0)" onclick="selectBook('', 'All Books')" class="book-option block px-4 py-2 text-sm text-slate-300 hover:bg-slate-700">All Books</a>
                                @foreach($books as $book)
                                    <a href="javascript:void(0)" onclick="selectBook('{{ $book->id }}')" class="book-option block px-4 py-2 text-sm text-slate-300 hover:bg-slate-700" data-search="{{ strtolower($book->book_name . ' ' . $book->id) }}">
                                        {{ $book->book_name }} <span class="text-xs text-indigo-400 ml-1">(ID: {{ $book->id }})</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Global Controls -->
        <div class="sticky top-4 z-50 mb-8 bg-slate-900/95 backdrop-blur-md p-5 rounded-xl border border-slate-700 shadow-2xl grid grid-cols-1 md:grid-cols-4 gap-4 transition-all">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">AI Voice Model</label>
                <select id="globalModel" class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 transition-colors text-slate-200 text-sm">
                    <option value="melo" selected>MeloTTS</option>
                    <option value="piper">Piper TTS</option>
                    <option value="fish">Fish Speech S2-Pro</option>
                    <option value="chattts">ChatTTS (Conversational)</option>
                    <option value="styletts2">StyleTTS2</option>
                    <option value="kokoro">Kokoro-82M</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Voice / Accent</label>
                <select id="globalVoice" class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 transition-colors text-slate-200 text-sm">
                    <!-- Populated via JS -->
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Speaking Speed</label>
                <input type="range" id="globalSpeed" min="0.5" max="2.0" step="0.1" value="1.0" class="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer mt-2">
                <div class="flex justify-between text-xs text-slate-400 mt-1">
                    <span>0.5x</span>
                    <span id="speedVal">1.0x</span>
                    <span>2.0x</span>
                </div>
            </div>
            <div class="flex items-center">
                <label class="flex items-center cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" id="globalDeepseek" class="sr-only" checked>
                        <div class="block bg-slate-700 w-10 h-6 rounded-full transition-colors"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform translate-x-full bg-indigo-500"></div>
                    </div>
                    <div class="ml-3 text-sm font-medium text-slate-300">
                        Preprocess with DeepSeek
                    </div>
                </label>
            </div>
            
            <!-- Model Specific Settings Row -->
            <div id="modelSpecificSettings" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 w-full">
                <!-- StyleTTS2: Diffusion Steps -->
                <div id="diffusionStepsContainer" style="display: none;" class="flex-col col-span-1 md:col-span-2">
                    <label class="mb-2 text-sm font-medium text-slate-300">Diffusion Steps (StyleTTS2)</label>
                    <div class="flex items-center space-x-4">
                        <input type="range" id="modalDiffusionSteps" min="3" max="20" step="1" value="5" class="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer">
                        <span id="modalDiffusionStepsVal" class="font-mono text-sm text-slate-300 w-8">5</span>
                    </div>
                </div>

                <!-- Kokoro Blending Settings -->
                <div id="kokoroBlendingContainer" style="display: none;" class="flex-col col-span-1 md:col-span-4 bg-slate-800 p-4 rounded-lg border border-slate-700">
                    <h4 class="text-sm font-bold text-indigo-400 mb-2">Voice Blending (Kokoro Only)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Voice 2 (Optional)</label>
                            <select id="modalVoice2Select" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 text-slate-200">
                                <option value="none" selected>None</option>
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Blend Method</label>
                            <select id="modalBlendMethodSelect" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 text-slate-200">
                                <option value="average" selected>Average</option>
                                <option value="linear">Linear Interpolation</option>
                                <option value="slerp">Spherical (Slerp)</option>
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Blend Ratio (<span id="modalBlendRatioVal">0.5</span>)</label>
                            <input type="range" id="modalBlendRatioSlider" min="0.0" max="1.0" step="0.05" value="0.5" class="w-full h-2 bg-slate-900 border border-slate-600 rounded-lg appearance-none cursor-pointer mt-2">
                        </div>
                    </div>
                </div>
                
                <!-- ChatTTS: Tweaks -->
                <div id="chatTtsSettings" class="hidden flex-row gap-4 col-span-1 md:col-span-4 flex-wrap">
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-slate-400 mb-1">Speed Tag</label>
                        <select id="chatttsSpeed" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-slate-200 focus:ring-1 focus:ring-indigo-500">
                            <option value="1">1 (Slowest)</option>
                            <option value="5" selected>5 (Normal)</option>
                            <option value="9">9 (Fastest)</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-slate-400 mb-1">Temp (0.1-1.0)</label>
                        <input type="number" id="chatttsTemp" step="0.1" min="0.1" max="1.0" value="0.3" class="w-24 px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-slate-400 mb-1">Top P (0.1-1.0)</label>
                        <input type="number" id="chatttsTopP" step="0.1" min="0.1" max="1.0" value="0.7" class="w-24 px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-slate-400 mb-1">Top K (1-50)</label>
                        <input type="number" id="chatttsTopK" step="1" min="1" max="50" value="20" class="w-24 px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @foreach($questions as $q)
                <div class="bg-slate-900 p-5 rounded-xl border border-slate-700" id="questionBlock-{{ $q->id }}">
                    <div class="flex flex-col lg:flex-row justify-between items-start gap-4">
                        <div class="flex-grow w-full">
                            <span class="inline-block px-2 py-1 bg-slate-700 text-xs font-semibold rounded text-slate-300 mb-2">Type {{ $q->type }} | ID: {{ $q->id }}</span>
                            <h3 class="text-lg font-medium text-white mb-3">{{ strip_tags($q->question_title) }}</h3>
                            
                            @if(isset($answers[$q->id]))
                                <div class="space-y-1 mt-2 text-sm text-slate-400">
                                    @foreach($answers[$q->id] as $ans)
                                        <div class="flex gap-2">
                                            <span class="font-medium text-slate-300">({{ strtolower($ans->sl_no) }})</span>
                                            <span>{{ strip_tags($ans->answer) }}</span>
                                            @if($q->type == 1)
                                                @php $cAns = strtoupper(trim($ans->correct_ans)); @endphp
                                                @if($cAns === 'T' || $cAns === '1')
                                                    <span class="text-green-400 text-xs ml-2 font-semibold">✓ (True)</span>
                                                @else
                                                    <span class="text-red-400 text-xs ml-2 font-semibold">✗ (False)</span>
                                                @endif
                                            @elseif($ans->correct_ans == 1)
                                                <span class="text-green-400 text-xs ml-2 font-semibold">✓ Correct</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            @if(!empty($q->correct_ans) && $q->type != 1)
                                <div class="mt-4 p-3 bg-indigo-900/30 border border-indigo-500/30 rounded-lg text-sm">
                                    <span class="font-semibold text-indigo-300">Correct Answer:</span> 
                                    <span class="text-slate-200">{{ $q->correct_ans }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-col gap-2 shrink-0 w-full lg:w-auto">
                            @php
                                $textToCopy = strip_tags($q->question_title) . "\n";
                                if (isset($answers[$q->id])) {
                                    foreach ($answers[$q->id] as $ans) {
                                        $textToCopy .= "(" . strtolower($ans->sl_no) . ") " . strip_tags($ans->answer);
                                        if ($q->type == 1) {
                                            $cAns = strtoupper(trim($ans->correct_ans));
                                            $textToCopy .= " " . (($cAns === 'T' || $cAns === '1') ? "(True)" : "(False)");
                                        }
                                        $textToCopy .= "\n";
                                    }
                                }
                                if (!empty($q->correct_ans) && $q->type != 1) {
                                    $textToCopy .= "\nCorrect Answer: " . trim($q->correct_ans) . "\n";
                                }
                            @endphp
                            
                            <button onclick="generateAudio({{ $q->id }}, this)" 
                                class="px-4 py-2 bg-green-600 text-white hover:bg-green-500 rounded-lg text-sm font-medium transition-colors whitespace-nowrap flex items-center justify-center gap-2">
                                <span class="spinner hidden"><svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                                <span>Generate Voice</span>
                            </button>
                            
                            <button onclick="copyToClipboard(this)" data-text="{{ htmlspecialchars($textToCopy, ENT_QUOTES) }}"
                                class="px-4 py-2 bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600/40 rounded-lg text-sm font-medium transition-colors whitespace-nowrap text-center">
                                Copy Text
                            </button>
                        </div>
                    </div>
                    
                    <!-- Playlist container -->
                    <div class="mt-6 border-t border-slate-700 pt-4">
                        <h4 class="text-sm font-medium text-slate-400 mb-3">Audio History</h4>
                        <div class="space-y-3 playlist-container" id="playlist-{{ $q->id }}">
                            @if(isset($questionAudios[$q->id]) && $questionAudios[$q->id]->count() > 0)
                                @foreach($questionAudios[$q->id] as $audio)
                                    <div class="bg-slate-800 p-4 rounded-lg border border-slate-600">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-xs text-slate-500">{{ $audio->created_at->diffForHumans() }} | ID: {{ $audio->id }}</span>
                                            <div class="flex gap-3">
                                                <a href="{{ asset('storage/' . $audio->file_path) }}" download class="text-xs text-indigo-400 hover:text-indigo-300">Download</a>
                                                <button onclick="deleteAudio({{ $audio->id }}, this)" class="text-xs text-red-400 hover:text-red-300">Delete</button>
                                            </div>
                                        </div>
                                        
                                        @if($audio->model_type)
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <span class="px-2 py-0.5 bg-slate-700 rounded text-[10px] text-slate-300">Model: {{ strtoupper($audio->model_type) }}</span>
                                            <span class="px-2 py-0.5 bg-slate-700 rounded text-[10px] text-slate-300">Voice: {{ $audio->voice }}</span>
                                            <span class="px-2 py-0.5 bg-slate-700 rounded text-[10px] text-slate-300">Speed: {{ $audio->speed }}x</span>
                                        </div>
                                        @endif
                                        
                                        <audio controls src="{{ asset('storage/' . $audio->file_path) }}" class="w-full h-8 mb-3 rounded"></audio>
                                        @if($audio->deepseek_text)
                                            <div class="text-xs bg-slate-900 p-3 rounded-md text-slate-400 border border-slate-700">
                                                <div class="font-semibold text-slate-300 mb-1">DeepSeek Preprocessed Script:</div>
                                                {{ $audio->deepseek_text }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="text-xs text-slate-500 italic empty-placeholder">No audio generated yet.</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $questions->links() }}
        </div>
    </div>

    <script>
        const dsCheck = document.getElementById('globalDeepseek');
        const dsDot = dsCheck.parentElement.querySelector('.dot');
        dsCheck.addEventListener('change', () => {
            if (dsCheck.checked) {
                dsDot.classList.add('translate-x-full', 'bg-indigo-500');
                dsDot.classList.remove('bg-slate-400');
            } else {
                dsDot.classList.remove('translate-x-full', 'bg-indigo-500');
                dsDot.classList.add('bg-slate-400');
            }
        });

        const globalModel = document.getElementById('globalModel');
        const globalVoice = document.getElementById('globalVoice');
        const globalSpeed = document.getElementById('globalSpeed');
        const speedVal = document.getElementById('speedVal');
        const diffSteps = document.getElementById('modalDiffusionSteps');
        const diffStepsVal = document.getElementById('modalDiffusionStepsVal');

        globalSpeed.addEventListener('input', (e) => {
            speedVal.innerText = parseFloat(e.target.value).toFixed(1) + 'x';
        });

        if (diffSteps && diffStepsVal) {
            diffSteps.addEventListener('input', (e) => {
                diffStepsVal.innerText = e.target.value;
            });
        }

        const voicesData = {
            'chattts': [
                { value: 'random', text: 'Random Voice' },
                { value: '1111', text: 'Voice Seed A (1111)' },
                { value: '2222', text: 'Voice Seed B (2222)' },
                { value: '3333', text: 'Voice Seed C (3333)' }
            ],
            'kokoro': [
                { value: 'af_heart', text: 'American Female (Heart)' },
                { value: 'am_michael', text: 'American Male (Michael)' },
                { value: 'am_adam', text: 'American Male (Adam)' }
            ],
            'styletts2': [
                { value: 'default', text: 'Default Voice (Male)' },
                { value: 'f-us-1', text: 'Female US 1' },
                { value: 'f-us-2', text: 'Female US 2' },
                { value: 'f-us-3', text: 'Female US 3' },
                { value: 'f-us-4', text: 'Female US 4' },
                { value: 'm-us-1', text: 'Male US 1' },
                { value: 'm-us-2', text: 'Male US 2' },
                { value: 'm-us-3', text: 'Male US 3' },
                { value: 'm-us-4', text: 'Male US 4' }
            ],
            'fish': [
                { value: 'default', text: 'Default English Voice' }
            ],
            'piper': [
                { value: 'en_US-lessac-high', text: 'Lessac (US English Female, High)' },
                { value: 'en_US-ryan-high', text: 'Ryan (US English Male, High)' }
            ],
            'melo': [
                { value: 'EN-US', text: 'American English (EN-US)' },
                { value: 'EN-BR', text: 'British English (EN-BR)' },
                { value: 'EN_INDIA', text: 'Indian English (EN_INDIA)' },
                { value: 'EN-AU', text: 'Australian English (EN-AU)' },
                { value: 'EN-Default', text: 'Default English (EN-Default)' }
            ]
        };

        function updateVoiceOptions() {
            const selectedModel = globalModel.value;
            
            // Show/Hide Model Specific settings
            document.getElementById('diffusionStepsContainer').style.display = (selectedModel === 'styletts2') ? 'flex' : 'none';
            document.getElementById('kokoroBlendingContainer').style.display = (selectedModel === 'kokoro') ? 'flex' : 'none';

            if (selectedModel === 'kokoro') {
                const voice2Select = document.getElementById('modalVoice2Select');
                voice2Select.innerHTML = '<option value="none" selected>None</option>';
                voicesData['kokoro'].forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.value;
                    opt.innerText = v.text;
                    voice2Select.appendChild(opt);
                });
            }

            if (selectedModel === 'chattts') {
                document.getElementById('chatTtsSettings').classList.remove('hidden');
                document.getElementById('globalSpeed').parentElement.classList.add('hidden');
            } else {
                document.getElementById('chatTtsSettings').classList.add('hidden');
                document.getElementById('globalSpeed').parentElement.classList.remove('hidden');
            }

            globalVoice.innerHTML = '';
            voicesData[selectedModel].forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.value;
                opt.innerText = v.text;
                globalVoice.appendChild(opt);
            });
        }

        globalModel.addEventListener('change', updateVoiceOptions);
        updateVoiceOptions();

        document.getElementById('modalBlendRatioSlider').addEventListener('input', function(e) {
            document.getElementById('modalBlendRatioVal').innerText = e.target.value;
        });

        function copyToClipboard(btn) {
            const text = btn.getAttribute('data-text');
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btn.innerText;
                btn.innerText = 'Copied!';
                btn.classList.add('bg-green-600/30', 'text-green-400');
                
                setTimeout(() => {
                    btn.innerText = originalText;
                    btn.classList.remove('bg-green-600/30', 'text-green-400');
                }, 2000);
            });
        }
        
        async function generateAudio(questionId, btn) {
            const spinner = btn.querySelector('.spinner');
            const btnText = btn.querySelectorAll('span')[1];
            
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            spinner.classList.remove('hidden');
            btnText.innerText = 'Processing...';
            
            const model = globalModel.value;
            const voice = globalVoice.value;
            const speed = globalSpeed.value;
            const preprocess = dsCheck.checked ? 1 : 0;
            const diffStepsElement = document.getElementById('modalDiffusionSteps');
            const diffStepsVal = diffStepsElement ? diffStepsElement.value : 5;
            
            try {
                const params = new URLSearchParams({
                    text: questionId, 
                    model_type: model,
                    voice: voice,
                    speed: speed,
                    preprocess_deepseek: preprocess,
                    diffusion_steps: diffStepsVal
                });

                if (model === 'chattts') {
                    params.append('chattts_speed', document.getElementById('chatttsSpeed').value);
                    params.append('chattts_temp', document.getElementById('chatttsTemp').value);
                    params.append('chattts_top_p', document.getElementById('chatttsTopP').value);
                    params.append('chattts_top_k', document.getElementById('chatttsTopK').value);
                } else if (model === 'kokoro') {
                    params.append('voice2', document.getElementById('modalVoice2Select').value);
                    params.append('blend_method', document.getElementById('modalBlendMethodSelect').value);
                    params.append('blend_ratio', document.getElementById('modalBlendRatioSlider').value);
                }
                
                const response = await fetch(`/text-to-audio/stream?${params.toString()}`);
                
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }
                
                const audioBlob = await response.blob();
                const audioUrl = URL.createObjectURL(audioBlob);
                
                const audioId = response.headers.get('X-Generated-Audio-ID');
                let deepseekText = response.headers.get('X-Generated-Deepseek-Text');
                if (deepseekText) {
                    deepseekText = atob(deepseekText);
                }
                
                const resModel = response.headers.get('X-Audio-Model') || model;
                const resVoice = response.headers.get('X-Audio-Voice') || voice;
                const resSpeed = response.headers.get('X-Audio-Speed') || speed;
                
                // Prepend to playlist
                const playlist = document.getElementById(`playlist-${questionId}`);
                const emptyState = playlist.querySelector('.empty-placeholder');
                if (emptyState) emptyState.remove();
                
                const div = document.createElement('div');
                div.className = 'bg-slate-800 p-4 rounded-lg border border-slate-600 border-l-4 border-l-green-500 mb-3';
                
                let html = `
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-green-400 font-semibold">Just now | ID: ${audioId || 'New'}</span>
                        <div class="flex gap-3">
                            <a href="${audioUrl}" download="audio_${questionId}.wav" class="text-xs text-indigo-400 hover:text-indigo-300">Download</a>
                            <button onclick="deleteAudio(${audioId}, this)" class="text-xs text-red-400 hover:text-red-300 ${!audioId ? 'hidden' : ''}">Delete</button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="px-2 py-0.5 bg-slate-700 rounded text-[10px] text-slate-300">Model: ${resModel.toUpperCase()}</span>
                        <span class="px-2 py-0.5 bg-slate-700 rounded text-[10px] text-slate-300">Voice: ${resVoice}</span>
                        <span class="px-2 py-0.5 bg-slate-700 rounded text-[10px] text-slate-300">Speed: ${resSpeed}x</span>
                    </div>
                    <audio controls src="${audioUrl}" class="w-full h-8 mb-3 rounded"></audio>
                `;
                
                if (deepseekText) {
                    html += `
                        <div class="text-xs bg-slate-900 p-3 rounded-md text-slate-400 border border-slate-700 mt-2">
                            <div class="font-semibold text-slate-300 mb-1">DeepSeek Preprocessed Script:</div>
                            ${deepseekText}
                        </div>
                    `;
                }
                
                div.innerHTML = html;
                playlist.insertBefore(div, playlist.firstChild);
                
                // Reset button text
                btnText.innerText = 'Success!';
                btn.classList.replace('bg-green-600', 'bg-green-500');
                
                setTimeout(() => {
                    btnText.innerText = 'Generate Voice';
                    btn.classList.replace('bg-green-500', 'bg-green-600');
                }, 2000);
                
            } catch (err) {
                console.error(err);
                alert("Failed to generate audio: " + err.message);
                btnText.innerText = 'Failed';
                btn.classList.replace('bg-green-600', 'bg-red-500');
                
                setTimeout(() => {
                    btnText.innerText = 'Generate Voice';
                    btn.classList.replace('bg-red-500', 'bg-green-600');
                }, 2000);
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
                spinner.classList.add('hidden');
            }
        }
        
        async function deleteAudio(id, btnElement) {
            if (!confirm('Are you sure you want to delete this audio?')) return;
            
            const card = btnElement.closest('.bg-slate-800');
            const originalText = btnElement.innerText;
            btnElement.innerText = 'Deleting...';
            btnElement.disabled = true;
            
            try {
                const response = await fetch(`/text-to-audio/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                if (response.ok) {
                    card.remove();
                } else {
                    throw new Error('Failed to delete');
                }
            } catch (err) {
                console.error(err);
                alert('Failed to delete audio.');
                btnElement.innerText = originalText;
                btnElement.disabled = false;
            }
        }
        function toggleDropdown() {
            document.getElementById('dropdownList').classList.toggle('hidden');
            if (!document.getElementById('dropdownList').classList.contains('hidden')) {
                document.getElementById('bookSearch').focus();
            }
        }

        function filterBooks() {
            let input = document.getElementById('bookSearch').value.toLowerCase();
            let options = document.querySelectorAll('.book-option');
            options.forEach(opt => {
                if (!opt.dataset.search) { opt.style.display = input === '' ? 'block' : 'none'; return; }
                if (opt.dataset.search.includes(input)) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            });
        }

        function selectBook(id) {
            document.getElementById('hiddenBookId').value = id;
            document.getElementById('filterForm').submit();
        }

        document.addEventListener('click', function(event) {
            let dropdown = document.getElementById('bookDropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                document.getElementById('dropdownList').classList.add('hidden');
            }
        });
    </script>
</body>
</html>
