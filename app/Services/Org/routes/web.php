<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Org — the church's staff pages (prefix /{organization}, RequireAssignment)
|--------------------------------------------------------------------------
|
| For EVERY staff member, not only admins: a Lifeshaper uses these too (registration now, check-in in R2).
| What each person may do is decided by AccessScope (plan §3.2), never by the URL. Ids in the URL are looked
| up within this church only. Satellite-specific pages (R2) will nest: /{organization}/{branch}/check-in.
*/

use App\Services\Org\Http\Controllers\Child\Controller as ChildController;
use App\Services\Org\Http\Controllers\Duplicate\Controller as DuplicateController;
use App\Services\Org\Http\Controllers\Family\Controller as FamilyController;
use App\Services\Org\Http\Controllers\Home\Controller as HomeController;
use App\Services\Org\Http\Controllers\People\Controller as PeopleController;
use App\Services\Org\Http\Controllers\Poster\Controller as PosterController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'show'])->name('org.home');

Route::get('/families', [FamilyController::class, 'index'])->name('org.families.index');
Route::get('/families/export', [FamilyController::class, 'export'])->name('org.families.export');
Route::get('/families/new', [FamilyController::class, 'register'])->name('org.families.register');
Route::post('/families', [FamilyController::class, 'store'])->name('org.families.store');
Route::get('/families/{family}', [FamilyController::class, 'show'])->whereNumber('family')->name('org.families.show');
Route::put('/families/{family}', [FamilyController::class, 'update'])->whereNumber('family')->name('org.families.update');
Route::post('/families/{family}/children', [FamilyController::class, 'addChild'])->whereNumber('family')->name('org.families.children.store');
Route::post('/families/{family}/resend', [FamilyController::class, 'resend'])->whereNumber('family')->middleware('throttle:10,1')->name('org.families.resend');
Route::put('/families/{family}/email', [FamilyController::class, 'correctEmail'])->whereNumber('family')->name('org.families.email');
Route::put('/children/{child}', [ChildController::class, 'update'])->whereNumber('child')->name('org.children.update');
Route::put('/children/{child}/photo-consent', [ChildController::class, 'recordPhotoConsent'])->whereNumber('child')->name('org.children.photo-consent');
Route::post('/children/{child}/photo', [ChildController::class, 'addPhoto'])->whereNumber('child')->name('org.children.photo');
Route::put('/children/{child}/status', [ChildController::class, 'updateStatus'])->whereNumber('child')->name('org.children.status');

Route::get('/people', [PeopleController::class, 'index'])->name('org.people.index');
Route::post('/people', [PeopleController::class, 'store'])->middleware('throttle:per-device')->name('org.people.store');
Route::delete('/people/roles/{assignment}', [PeopleController::class, 'removeRole'])->whereNumber('assignment')->name('org.people.roles.destroy');

Route::get('/duplicates', [DuplicateController::class, 'index'])->name('org.duplicates.index');
Route::put('/duplicates/{pair}', [DuplicateController::class, 'resolve'])->whereNumber('pair')->name('org.duplicates.resolve');

Route::get('/poster', [PosterController::class, 'show'])->name('org.poster');
