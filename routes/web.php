<?php

use App\Http\Controllers\TikTokOAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn () => view('landing'));

Route::view('/terms-of-service', 'legal.terms-of-service')->name('legal.terms');
Route::view('/privacy-policy', 'legal.privacy-policy')->name('legal.privacy');

Route::get('/tiktok/callback', [TikTokOAuthController::class, 'callback'])->name('tiktok.callback');

Route::get('/klaus-media/{path}', function (string $path) {
    abort_unless(Storage::disk('local')->exists($path), 404);

    return response()->file(Storage::disk('local')->path($path));
})->where('path', '.*')->name('klaus.media');
