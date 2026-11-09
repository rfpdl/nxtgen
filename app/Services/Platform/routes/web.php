<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Platform — the platform admin's area (prefix /platform, RequirePlatformAdmin)
|--------------------------------------------------------------------------
|
| Spec item 12: the church's satellites, its people and every Role, and its settings. 404 for anyone but the
| platform admin, who is set at the terminal (nxtgen:platform-admin).
*/

use App\Services\Platform\Http\Controllers\People\Controller as PeopleController;
use App\Services\Platform\Http\Controllers\Satellite\Controller as SatelliteController;
use App\Services\Platform\Http\Controllers\Settings\Controller as SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SatelliteController::class, 'index'])->name('satellites.index');
Route::post('/satellites', [SatelliteController::class, 'store'])->name('satellites.store');
Route::put('/satellites/{satellite}', [SatelliteController::class, 'update'])->name('satellites.update');

Route::get('/people', [PeopleController::class, 'index'])->name('people.index');
Route::post('/people', [PeopleController::class, 'store'])->middleware('throttle:per-device')->name('people.store');
Route::delete('/people/roles/{role}', [PeopleController::class, 'destroy'])->whereNumber('role')->name('people.roles.destroy');

Route::get('/settings', [SettingsController::class, 'show'])->name('settings.show');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
