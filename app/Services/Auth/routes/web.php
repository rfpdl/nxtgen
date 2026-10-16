<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Auth — one sign-in for everyone, no role picker (plan §4.5)
|--------------------------------------------------------------------------
|
| Links are emailed per purpose so the URL says what it does: /magic/login, /magic/verify, /magic/claim.
*/

use App\Enums\MagicLinkRouteEnum;
use App\Services\Auth\Http\Controllers\Account\Controller as AccountController;
use App\Services\Auth\Http\Controllers\Landing\Controller as LandingController;
use App\Services\Auth\Http\Controllers\Link\Controller as LinkController;
use App\Services\Auth\Http\Controllers\Login\Controller as LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login/password', [LoginController::class, 'password'])->middleware(['guest', 'throttle:password-login'])->name('auth.password.login');
Route::post('/login/link', [LoginController::class, 'requestLink'])->middleware('throttle:magic-link')->name('auth.link.request');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// GET only shows a Continue button (email scanners fetch links); the POST is what uses the link.
Route::get('/magic/{purpose}/{token}', [LinkController::class, 'confirm'])->whereIn('purpose', MagicLinkRouteEnum::segments())->name('auth.magic.confirm');
Route::post('/magic/login/{token}', [LinkController::class, 'login'])->middleware('throttle:per-device')->name('auth.magic.login');
Route::post('/magic/verify/{token}', [LinkController::class, 'verify'])->middleware('throttle:per-device')->name('auth.magic.verify');
Route::post('/magic/claim/{token}', [LinkController::class, 'claim'])->middleware('throttle:per-device')->name('auth.magic.claim');

Route::get('/home', [LandingController::class, 'show'])->middleware('auth')->name('home');
Route::post('/home/choice', [LandingController::class, 'choose'])->middleware('auth')->name('home.choice');

Route::get('/account/password', [AccountController::class, 'showPassword'])->middleware('auth')->name('account.password');
Route::put('/account/password', [AccountController::class, 'updatePassword'])->middleware('auth')->name('account.password.update');
