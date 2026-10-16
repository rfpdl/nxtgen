<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Family — a parent's own family page (prefix /{church}/family, RequireGuardian)
|--------------------------------------------------------------------------
|
| Release 1: view the family, add a child, edit a child's details. The family always comes from the
| signed-in parent, never from the URL or the request body.
*/

use App\Services\Family\Http\Controllers\Child\Controller as ChildController;
use App\Services\Family\Http\Controllers\Home\Controller as HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'show'])->name('family.show');
Route::put('/', [HomeController::class, 'update'])->name('family.update');
Route::post('/children', [ChildController::class, 'store'])->name('family.children.store');
Route::put('/children/{child}', [ChildController::class, 'update'])->whereNumber('child')->name('family.children.update');
Route::post('/children/{child}/photo', [ChildController::class, 'addPhoto'])->whereNumber('child')->name('family.children.photo');
Route::put('/children/{child}/photo-consent', [ChildController::class, 'givePhotoConsent'])->whereNumber('child')->name('family.children.photo-consent.give');
Route::delete('/children/{child}/photo-consent', [ChildController::class, 'withdrawPhotoConsent'])->whereNumber('child')->name('family.children.photo-consent');
