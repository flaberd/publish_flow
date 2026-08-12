<?php

use App\Http\Controllers\Instagram\OAuthController as InstagramOAuthController;
use App\Http\Controllers\TikTok\OAuthController as TikTokOAuthController;
use App\Livewire\Accounts;
use App\Livewire\Dashboard;
use App\Livewire\Login;
use App\Livewire\Privacy;
use App\Livewire\Terms;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)
    ->middleware('auth')
    ->name('dashboard');

Route::livewire('/accounts', Accounts::class)
    ->middleware('auth')
    ->name('accounts');

Route::livewire('/login', Login::class)
    ->middleware('guest')
    ->name('login');

Route::livewire('/terms', Terms::class)->name('terms');

Route::livewire('/privacy', Privacy::class)->name('privacy');

Route::get('/instagram/connect', [InstagramOAuthController::class, 'redirect'])
    ->middleware('auth')
    ->name('instagram.connect');

Route::get('/instagram/callback', [InstagramOAuthController::class, 'callback'])
    ->middleware('auth')
    ->name('instagram.callback');

Route::get('/tiktok/connect', [TikTokOAuthController::class, 'redirect'])
    ->middleware('auth')
    ->name('tiktok.connect');

Route::get('/tiktok/callback', [TikTokOAuthController::class, 'callback'])
    ->middleware('auth')
    ->name('tiktok.callback');

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');
