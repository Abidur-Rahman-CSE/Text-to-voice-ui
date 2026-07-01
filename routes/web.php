<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TextToAudioController;

Route::get('/', function () {
    return redirect('/text-to-audio');
});

Route::get('/text-to-audio', [TextToAudioController::class, 'index'])->name('audio.index');
Route::post('/text-to-audio/synthesize', [TextToAudioController::class, 'synthesize'])->name('audio.synthesize');
Route::get('/text-to-audio/stream', [TextToAudioController::class, 'stream'])->name('audio.stream');
Route::get('/text-to-audio/latest', [TextToAudioController::class, 'latest'])->name('audio.latest');
Route::get('/question-library', [TextToAudioController::class, 'questionLibrary'])->name('audio.library');
Route::delete('/text-to-audio/{id}', [TextToAudioController::class, 'destroy'])->name('audio.destroy');
