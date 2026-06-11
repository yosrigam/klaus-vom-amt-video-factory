<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn () => view('landing'));

Route::get('/klaus-media/{path}', function (string $path) {
    abort_unless(Storage::disk('local')->exists($path), 404);

    return response()->file(Storage::disk('local')->path($path));
})->where('path', '.*')->name('klaus.media');
