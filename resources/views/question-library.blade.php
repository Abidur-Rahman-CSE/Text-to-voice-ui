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
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                Question Library (Type 1 & 2)
            </h2>
            <p class="text-slate-400 text-sm mt-1">Live from {{ env('DB_QUESTIONS_DATABASE') }}</p>
        </div>

        <div class="space-y-6">
            @foreach($questions as $q)
                <div class="bg-slate-900 p-5 rounded-xl border border-slate-700">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex-grow">
                            <span class="inline-block px-2 py-1 bg-slate-700 text-xs font-semibold rounded text-slate-300 mb-2">Type {{ $q->type }}</span>
                            <h3 class="text-lg font-medium text-white mb-3">{{ strip_tags($q->question_title) }}</h3>
                            
                            @if(isset($answers[$q->id]))
                                <div class="space-y-1 mt-2 text-sm text-slate-400">
                                    @foreach($answers[$q->id] as $ans)
                                        <div class="flex gap-2">
                                            <span class="font-medium text-slate-300">({{ strtolower($ans->sl_no) }})</span>
                                            <span>{{ strip_tags($ans->answer) }}</span>
                                            @if($ans->correct_ans == 1)
                                                <span class="text-green-400 text-xs ml-2">✓ Correct</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div>
                            @php
                                $textToCopy = strip_tags($q->question_title) . "\n";
                                if (isset($answers[$q->id])) {
                                    foreach ($answers[$q->id] as $ans) {
                                        $textToCopy .= "(" . strtolower($ans->sl_no) . ") " . strip_tags($ans->answer) . " " . ($ans->correct_ans == 1 ? "(True)" : "(False)") . "\n";
                                    }
                                }
                            @endphp
                            <button onclick="copyToClipboard(this)" data-text="{{ htmlspecialchars($textToCopy, ENT_QUOTES) }}"
                                class="px-4 py-2 bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600/40 rounded-lg text-sm font-medium transition-colors whitespace-nowrap">
                                Copy Text
                            </button>
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
    </script>
</body>
</html>
