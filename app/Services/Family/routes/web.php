<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Family — a parent's own family page (prefix /{organization}/family, RequireGuardian)
|--------------------------------------------------------------------------
|
| Release 1: view the family, add a child, edit a child's details. The family always comes from the
| signed-in parent, never from the URL or the request body.
*/

use App\Services\Family\Http\Controllers\Child\Controller as ChildController;
use App\Services\Family\Http\Controllers\Home\Controller as HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'show'])->name('family.show');
Route::post('/children', [ChildController::class, 'store'])->name('family.children.store');
Route::put('/children/{child}', [ChildController::class, 'update'])->whereNumber('child')->name('family.children.update');
