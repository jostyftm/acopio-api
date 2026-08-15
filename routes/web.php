<?php

use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/up', fn () => response()->json(['status' => 'up']));

Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('auth.callback');
