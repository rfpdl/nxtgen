<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Family — a child's photo (no prefix: /photos/children/{child})
|--------------------------------------------------------------------------
|
| Parents AND staff open it, so it sits outside /{church}/family and RequireGuardian: the link is signed and
| short-lived, and ShowChildPhotoFeature checks who is asking against the child's own church.
*/

use App\Services\Family\Http\Controllers\Photo\Controller as PhotoController;
use Illuminate\Support\Facades\Route;

Route::get('/photos/children/{child}', [PhotoController::class, 'child'])->whereNumber('child')->middleware(['auth', 'signed'])->name('photos.child');
