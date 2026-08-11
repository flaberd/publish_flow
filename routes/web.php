<?php

use App\Http\Controllers\Instagram\OAuthController as InstagramOAuthController;
use App\Livewire\Accounts;
use App\Livewire\Dashboard;
use App\Livewire\Login;
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

Route::get('/instagram/connect', [InstagramOAuthController::class, 'redirect'])
    ->middleware('auth')
    ->name('instagram.connect');

Route::get('/instagram/callback', [InstagramOAuthController::class, 'callback'])
    ->middleware('auth')
    ->name('instagram.callback');

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');
