<?php

use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/up', fn () => response()->json(['status' => 'up']));

Route::get('login', [SocialAuthController::class, 'index'])->name('login');

Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->name('auth.redirect');

Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('auth.callback');
