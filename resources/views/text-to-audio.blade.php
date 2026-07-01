<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Text to Speech - Testing UI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .log-container::-webkit-scrollbar { width: 6px; }
        .log-container::-webkit-scrollbar-track { background: #1f2937; }
        .log-container::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 3px; }
    </style>
</head>
<body class="bg-slate-900 text-slate-200 min-h-screen p-4 md:p-8 font-sans">

    <div class="max-w-7xl mx-auto mb-6 bg-slate-800 rounded-xl p-4 flex gap-4 shadow-xl border border-slate-700">
        <a href="{{ route('audio.index') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-500 transition-colors">
            Manual Input
        </a>
        <a href="{{ route('audio.library') }}" class="px-4 py-2 rounded-lg bg-slate-700 text-slate-300 font-medium hover:bg-slate-600 hover:text-white transition-colors">
            Question Library
        </a>
    </div>

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Input (Span 1 or 2) -->
        <div class="lg:col-span-1 bg-slate-800 p-6 rounded-2xl shadow-2xl border border-slate-700 flex flex-col h-full">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                    Fish Speech AI
                </h2>
                <p class="text-slate-400 text-sm mt-1">Local TTS Model Testing Interface</p>
            </div>

            <div class="flex-grow flex flex-col space-y-4">
                <div class="flex-grow flex flex-col">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Input Text</label>
                    <textarea id="textInput" 
                        class="flex-grow w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none text-slate-200 placeholder-slate-500"
                        placeholder="Once upon a time in a digital world..."></textarea>
                </div>
                <div class="flex flex-col mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">AI Voice Model</label>
                    <select id="modelSelect" class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-slate-200">
                        <option value="melo" selected>MeloTTS</option>
                        <option value="piper">Piper TTS</option>
                        <option value="fish">Fish Speech S2-Pro</option>
                        <option value="chattts">ChatTTS (Conversational)</option>
                        <option value="styletts2">StyleTTS2</option>
                        <option value="kokoro">Kokoro-82M</option>
                    </select>
                </div>
                <div class="flex flex-col mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Voice / Accent</label>
                    <select id="voiceSelect" class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-slate-200">
                        <!-- Populated by JS -->
                    </select>
                </div>
                
                <div class="flex flex-col mb-4" id="diffusionStepsContainer" style="display: none;">
                    <label class="mb-2 font-semibold text-slate-300 text-sm">Diffusion Steps</label>
                    <div class="flex items-center space-x-4">
                        <input type="range" name="diffusion_steps" id="diffusion_steps" min="3" max="20" step="1" value="5" class="w-full h-2 bg-slate-900 border border-slate-600 rounded-lg appearance-none cursor-pointer">
                        <span id="diffusion_steps_val" class="font-mono text-sm w-8 text-slate-200">5</span>
                    </div>
                </div>

                <div class="flex flex-col mb-4" id="standardSpeedContainer">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Speaking Speed</label>
                    <select id="speedSelect" class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-slate-200">
                        <option value="1.0">Normal (1.0x)</option>
                        <option value="0.9">Slightly Slower (0.9x)</option>
                        <option value="0.8">Slower (0.8x)</option>
                        <option value="0.7">Very Slow (0.7x)</option>
                    </select>
                </div>

                <!-- Kokoro Blending Settings -->
                <div id="kokoroBlendingContainer" class="hidden mb-4 p-4 bg-slate-800 border border-slate-700 rounded-xl space-y-4 shadow-inner">
                    <h4 class="text-sm font-bold text-indigo-400 mb-2">Voice Blending (Kokoro Only)</h4>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="flex flex-col">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Voice 2 (Optional)</label>
                            <select id="voice2Select" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 text-slate-200">
                                <option value="none" selected>None</option>
                                <!-- Populated dynamically by JS -->
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col">
                                <label class="block text-xs font-medium text-slate-400 mb-1">Blend Method</label>
                                <select id="blendMethodSelect" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 text-slate-200">
                                    <option value="average" selected>Average</option>
                                    <option value="linear">Linear Interpolation</option>
                                    <option value="slerp">Spherical (Slerp)</option>
                                </select>
                            </div>
                            <div class="flex flex-col">
                                <label class="block text-xs font-medium text-slate-400 mb-1">Blend Ratio (<span id="blendRatioVal">0.5</span>)</label>
                                <input type="range" id="blendRatioSlider" min="0.0" max="1.0" step="0.05" value="0.5" class="w-full h-2 bg-slate-900 border border-slate-600 rounded-lg appearance-none cursor-pointer mt-2">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ChatTTS Advanced Settings -->
                <div id="chatTtsSettings" class="hidden mb-4 p-4 bg-slate-800 border border-slate-700 rounded-xl space-y-4 shadow-inner">
                    <h4 class="text-sm font-bold text-indigo-400 mb-2">ChatTTS Tweaks</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Speed Tag [speed_X]</label>
                            <select id="chatttsSpeed" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 text-slate-200">
                                <option value="1">1 (Slowest)</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4 (Slower)</option>
                                <option value="5" selected>5 (Default/Normal)</option>
                                <option value="6">6</option>
                                <option value="7">7 (Faster)</option>
                                <option value="8">8</option>
                                <option value="9">9 (Fastest)</option>
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Temperature (0.1 - 1.0)</label>
                            <input type="number" id="chatttsTemp" step="0.1" min="0.1" max="1.0" value="0.3" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-sm text-slate-200 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div class="flex flex-col">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Top P (0.1 - 1.0)</label>
                            <input type="number" id="chatttsTopP" step="0.1" min="0.1" max="1.0" value="0.7" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-sm text-slate-200 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div class="flex flex-col">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Top K (1 - 50)</label>
                            <input type="number" id="chatttsTopK" step="1" min="1" max="50" value="20" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-sm text-slate-200 focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <div class="flex items-center mb-6">
                    <input type="checkbox" id="preprocessDeepseek" class="w-4 h-4 text-indigo-600 bg-slate-900 border-slate-600 rounded focus:ring-indigo-500 focus:ring-2" checked>
                    <label for="preprocessDeepseek" class="ml-2 text-sm font-medium text-slate-300">
                        Pre-process Medical Text with DeepSeek AI
                    </label>
                </div>

                <button id="generateBtn" 
                    class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 flex justify-center items-center gap-3 shadow-lg shadow-indigo-500/20">
                    <svg id="loadingIcon" class="animate-spin h-5 w-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="btnText">Send to AI Model</span>
                </button>
            </div>

            <!-- Audio Player for Latest -->
            <div id="audioContainer" class="hidden mt-6 pt-6 border-t border-slate-700">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-green-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Generation Complete!
                    </p>
                    <span id="finalTime" class="text-xs font-mono bg-slate-900 px-2 py-1 rounded text-slate-400"></span>
                </div>
                <audio id="audioPlayer" controls class="w-full rounded-lg h-12 bg-slate-900">
                    Your browser does not support the audio element.
                </audio>
                <div class="mt-2 text-right">
                    <a id="downloadLink" href="#" download class="text-xs text-indigo-400 hover:text-indigo-300">Download Audio</a>
                </div>
            </div>
        </div>

        <!-- Middle Column: Logs & Info -->
        <div class="lg:col-span-1 bg-black p-4 rounded-2xl shadow-2xl border border-slate-700 flex flex-col h-[600px] lg:h-auto font-mono text-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-8 bg-slate-800 flex items-center px-4 border-b border-slate-700 z-10">
                <div class="flex space-x-2">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <span class="ml-4 text-xs text-slate-400 uppercase tracking-wider">System Logs</span>
                <div class="ml-auto flex items-center gap-2">
                    <span id="timerDisplay" class="text-emerald-400 font-bold hidden">0.0s</span>
                </div>
            </div>

            <div id="logWindow" class="log-container mt-8 flex-grow overflow-y-auto p-2 space-y-1.5 pb-4">
                <div class="text-slate-500">System initialized. Ready for input...</div>
            </div>
        </div>

        <!-- Right Column: History -->
        <div class="lg:col-span-1 bg-slate-800 p-6 rounded-2xl shadow-2xl border border-slate-700 flex flex-col h-[600px] lg:h-auto overflow-hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-white">Generated History</h3>
                <p class="text-xs text-slate-400">Past audios saved to server</p>
            </div>
            
            <div class="flex-grow overflow-y-auto log-container space-y-3 pr-2" id="historyContainer">
                @forelse($audios as $audio)
                <div class="bg-slate-900 p-4 rounded-xl border border-slate-700">
                    <p class="text-xs text-slate-400 mb-2 truncate" title="{{ $audio->text }}">
                        "{{ Str::limit($audio->text, 40) }}"
                    </p>
                    <audio controls src="{{ asset('storage/' . $audio->file_path) }}" class="w-full h-8 rounded mb-2"></audio>
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>{{ $audio->created_at->diffForHumans() }}</span>
                        <a href="{{ asset('storage/' . $audio->file_path) }}" download class="text-indigo-400 hover:text-indigo-300">Download</a>
                    </div>
                </div>
                @empty
                <div class="text-sm text-slate-500 text-center mt-10">No generated audio yet.</div>
                @endforelse
            </div>
        </div>

    </div>

    <script>
        const btn = document.getElementById('generateBtn');
        const btnText = document.getElementById('btnText');
        const loadingIcon = document.getElementById('loadingIcon');
        const textInput = document.getElementById('textInput');
        const audioContainer = document.getElementById('audioContainer');
        const audioPlayer = document.getElementById('audioPlayer');
        const downloadLink = document.getElementById('downloadLink');
        const logWindow = document.getElementById('logWindow');
        const timerDisplay = document.getElementById('timerDisplay');
        const finalTime = document.getElementById('finalTime');
        const historyContainer = document.getElementById('historyContainer');
        const modelSelect = document.getElementById('modelSelect');
        const voiceSelect = document.getElementById('voiceSelect');
        const diffusionSteps = document.getElementById('diffusion_steps');
        const diffusionStepsVal = document.getElementById('diffusion_steps_val');

        diffusionSteps.addEventListener('input', () => { diffusionStepsVal.innerText = diffusionSteps.value; });

        const voicesData = {
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
            const selectedModel = modelSelect.value;
            voiceSelect.innerHTML = '';
            voicesData[selectedModel].forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.value;
                opt.innerText = v.text;
                voiceSelect.appendChild(opt);
            });

            // Show/hide specific settings
            document.getElementById('diffusionStepsContainer').style.display = (selectedModel === 'styletts2') ? 'flex' : 'none';
            document.getElementById('kokoroBlendingContainer').style.display = (selectedModel === 'kokoro') ? 'block' : 'none';

            if (selectedModel === 'kokoro') {
                const voice2Select = document.getElementById('voice2Select');
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
                document.getElementById('standardSpeedContainer').classList.add('hidden');
            } else {
                document.getElementById('chatTtsSettings').classList.add('hidden');
                document.getElementById('standardSpeedContainer').classList.remove('hidden');
            }
        }

        modelSelect.addEventListener('change', updateVoiceOptions);
        updateVoiceOptions(); // Initialize

        document.getElementById('blendRatioSlider').addEventListener('input', function(e) {
            document.getElementById('blendRatioVal').innerText = e.target.value;
        });

        let timerInterval;
        let startTime;

        function appendLog(message, type = 'info', data = null) {
            const div = document.createElement('div');
            const now = new Date();
            const timeStr = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}.${now.getMilliseconds().toString().padStart(3, '0')}`;
            
            let colorClass = 'text-slate-300';
            let prefix = '[INFO]';
            
            if (type === 'success') { colorClass = 'text-green-400'; prefix = '[OK]'; }
            else if (type === 'error') { colorClass = 'text-red-400'; prefix = '[ERR]'; }
            else if (type === 'process') { colorClass = 'text-blue-400'; prefix = '[EXEC]'; }
            else if (type === 'warning') { colorClass = 'text-yellow-400'; prefix = '[WARN]'; }

            let content = `<span class="text-slate-500 mr-2">${timeStr}</span> <span class="${colorClass} font-semibold mr-2">${prefix}</span> <span class="text-slate-300">${message}</span>`;
            
            if (data) {
                content += `<pre class="mt-1 ml-6 p-2 bg-slate-900 border border-slate-700 rounded text-xs text-indigo-300 overflow-x-auto">${typeof data === 'object' ? JSON.stringify(data, null, 2) : data}</pre>`;
            }

            div.innerHTML = content;
            logWindow.appendChild(div);
            logWindow.scrollTop = logWindow.scrollHeight;
        }

        function startTimer() {
            startTime = Date.now();
            timerDisplay.classList.remove('hidden');
            timerDisplay.innerText = '0.0s';
            timerInterval = setInterval(() => {
                const diff = (Date.now() - startTime) / 1000;
                timerDisplay.innerText = diff.toFixed(1) + 's';
            }, 100);
        }

        function stopTimer() {
            clearInterval(timerInterval);
            const diff = (Date.now() - startTime) / 1000;
            return diff.toFixed(2);
        }

        function prependToHistory(data) {
            const emptyState = historyContainer.querySelector('.text-center');
            if (emptyState) emptyState.remove();

            const div = document.createElement('div');
            div.className = 'bg-slate-900 p-4 rounded-xl border border-slate-700 mb-3';
            div.innerHTML = `
                <p class="text-xs text-slate-400 mb-2 truncate" title="${data.text}">
                    "${data.text.length > 40 ? data.text.substring(0, 40) + '...' : data.text}"
                </p>
                <audio controls src="${data.url}" class="w-full h-8 rounded mb-2"></audio>
                <div class="flex justify-between items-center text-xs text-slate-500">
                    <span>Just now</span>
                    <a href="${data.url}" download class="text-indigo-400 hover:text-indigo-300">Download</a>
                </div>
            `;
            historyContainer.prepend(div);
        }

        btn.addEventListener('click', async () => {
            const text = textInput.value.trim();
            if (!text) {
                appendLog('Empty input rejected.', 'warning');
                return;
            }

            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btnText.innerText = 'AI is Processing...';
            loadingIcon.classList.remove('hidden');
            audioContainer.classList.add('hidden');
            logWindow.innerHTML = '';
            
            appendLog('Process started. Preparing data payload...', 'process');
            
            const payload = { 
                text: text,
                model_type: modelSelect.value,
                voice: voiceSelect.value,
                speed: document.getElementById('speedSelect').value,
                diffusion_steps: diffusionSteps.value,
                preprocess_deepseek: document.getElementById('preprocessDeepseek').checked
            };
            appendLog('Payload created:', 'info', payload);
            
            appendLog('Connecting to Laravel Backend...', 'process');
            startTimer();

            try {
                await new Promise(r => setTimeout(r, 400)); 
                
                const modelType = modelSelect.value;
                const voice = voiceSelect.value;
                const speed = document.getElementById('speedSelect').value;
                const preprocess = document.getElementById('preprocessDeepseek').checked ? '1' : '0';
                
                let streamUrl = `{{ route('audio.stream') }}?text=${encodeURIComponent(text)}&model_type=${encodeURIComponent(modelType)}&voice=${encodeURIComponent(voice)}&speed=${encodeURIComponent(speed)}&diffusion_steps=${encodeURIComponent(diffusionSteps.value)}&preprocess_deepseek=${preprocess}`;
                
                if (modelType === 'chattts') {
                    streamUrl += `&chattts_speed=${encodeURIComponent(document.getElementById('chatttsSpeed').value)}`;
                    streamUrl += `&chattts_temp=${encodeURIComponent(document.getElementById('chatttsTemp').value)}`;
                    streamUrl += `&chattts_top_p=${encodeURIComponent(document.getElementById('chatttsTopP').value)}`;
                    streamUrl += `&chattts_top_k=${encodeURIComponent(document.getElementById('chatttsTopK').value)}`;
                } else if (modelType === 'kokoro') {
                    streamUrl += `&voice2=${encodeURIComponent(document.getElementById('voice2Select').value)}`;
                    streamUrl += `&blend_method=${encodeURIComponent(document.getElementById('blendMethodSelect').value)}`;
                    streamUrl += `&blend_ratio=${encodeURIComponent(document.getElementById('blendRatioSlider').value)}`;
                }
                
                audioPlayer.src = streamUrl;
                audioContainer.classList.remove('hidden');

                // Handle successful stream start
                audioPlayer.onplaying = async () => {
                    if (timerDisplay.classList.contains('hidden')) return; // Already processed

                    const timeTaken = stopTimer();
                    finalTime.innerText = `Started playing in ${timeTaken}s`;
                    appendLog(`First chunk received! Audio playing in ${timeTaken} seconds!`, 'success');
                    
                    // Reset UI
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    btnText.innerText = 'Send to AI Model';
                    loadingIcon.classList.add('hidden');
                    timerDisplay.classList.add('hidden');

                    // Fetch latest history
                    try {
                        const res = await fetch("{{ route('audio.latest') }}");
                        if(res.ok) {
                            const data = await res.json();
                            prependToHistory(data);
                            downloadLink.href = data.url;
                        }
                    } catch(e) {}
                };

                // Handle stream errors
                audioPlayer.onerror = () => {
                    stopTimer();
                    appendLog('Stream connection failed. Check Python server.', 'error');
                    
                    // Reset UI
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    btnText.innerText = 'Send to AI Model';
                    loadingIcon.classList.add('hidden');
                    timerDisplay.classList.add('hidden');
                };

                audioPlayer.play().catch(e => {
                    appendLog('Auto-play blocked by browser. Click play manually.', 'warning');
                });

            } catch (error) {
                stopTimer();
                appendLog(error.message, 'error');
                
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btnText.innerText = 'Send to AI Model';
                loadingIcon.classList.add('hidden');
                timerDisplay.classList.add('hidden');
            }
        });

        textInput.value = "Welcome to the Fish Speech testing dashboard. The AI model will process this text and return a high quality human voice.";
    </script>
</body>
</html>
