<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Public — what anyone can open without signing in
|--------------------------------------------------------------------------
|
| The church's registration link and QR code point here, so these URLs must stay stable.
*/

use App\Services\Public\Http\Controllers\Registration\Controller as RegistrationController;
use App\Services\Public\Http\Controllers\Welcome\Controller as WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'show'])->name('public.welcome');

// The church's registration link and QR code. Throttled per IP (security list #7).
Route::get('/{church:slug}/register', [RegistrationController::class, 'show'])->name('public.register');
Route::post('/{church:slug}/register', [RegistrationController::class, 'store'])->middleware('throttle:per-device')->name('public.register.store');
Route::get('/{church:slug}/register/thanks', [RegistrationController::class, 'thanks'])->name('public.register.thanks');
