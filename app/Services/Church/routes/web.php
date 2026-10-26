<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Church — the church's staff pages (prefix /{church}, RequireAssignment)
|--------------------------------------------------------------------------
|
| For EVERY staff member, not only admins: a Lifeshaper uses these too (registration now, check-in in R2).
| What each person may do is decided by AccessScope (plan §3.2), never by the URL. Ids in the URL are looked
| up within this church only. Satellite-specific pages (R2) will nest: /{church}/{satellite}/check-in.
*/

use App\Services\Church\Http\Controllers\Child\Controller as ChildController;
use App\Services\Church\Http\Controllers\Duplicate\Controller as DuplicateController;
use App\Services\Church\Http\Controllers\Family\Controller as FamilyController;
use App\Services\Church\Http\Controllers\Home\Controller as HomeController;
use App\Services\Church\Http\Controllers\People\Controller as PeopleController;
use App\Services\Church\Http\Controllers\Poster\Controller as PosterController;
use App\Services\Church\Http\Controllers\Schedule\Controller as ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'show'])->name('church.home');

Route::get('/families', [FamilyController::class, 'index'])->name('church.families.index');
Route::get('/families/export', [FamilyController::class, 'export'])->name('church.families.export');
Route::get('/families/new', [FamilyController::class, 'register'])->name('church.families.register');
Route::post('/families', [FamilyController::class, 'store'])->name('church.families.store');
Route::get('/families/{family}', [FamilyController::class, 'show'])->whereNumber('family')->name('church.families.show');
Route::put('/families/{family}', [FamilyController::class, 'update'])->whereNumber('family')->name('church.families.update');
Route::post('/families/{family}/children', [FamilyController::class, 'addChild'])->whereNumber('family')->name('church.families.children.store');
Route::post('/families/{family}/resend', [FamilyController::class, 'resend'])->whereNumber('family')->middleware('throttle:10,1')->name('church.families.resend');
Route::put('/families/{family}/email', [FamilyController::class, 'correctEmail'])->whereNumber('family')->name('church.families.email');
Route::get('/children', [ChildController::class, 'index'])->name('church.children.index');
Route::put('/children/{child}/level', [ChildController::class, 'updateLevel'])->whereNumber('child')->name('church.children.level');
Route::put('/children/{child}', [ChildController::class, 'update'])->whereNumber('child')->name('church.children.update');
Route::put('/children/{child}/photo-consent', [ChildController::class, 'recordPhotoConsent'])->whereNumber('child')->name('church.children.photo-consent');
Route::post('/children/{child}/photo', [ChildController::class, 'addPhoto'])->whereNumber('child')->name('church.children.photo');
Route::put('/children/{child}/status', [ChildController::class, 'updateStatus'])->whereNumber('child')->name('church.children.status');

Route::get('/people', [PeopleController::class, 'index'])->name('church.people.index');
Route::post('/people', [PeopleController::class, 'store'])->middleware('throttle:per-device')->name('church.people.store');
Route::delete('/people/roles/{assignment}', [PeopleController::class, 'removeRole'])->whereNumber('assignment')->name('church.people.roles.destroy');

// Classes (GLOSSARY "Schedule"): admins make a satellite's classes (DECISIONS 2026-10-26).
Route::get('/classes', [ScheduleController::class, 'index'])->name('church.schedules.index');
Route::post('/classes', [ScheduleController::class, 'store'])->name('church.schedules.store');
Route::put('/classes/{schedule}', [ScheduleController::class, 'update'])->whereNumber('schedule')->name('church.schedules.update');
Route::put('/classes/{schedule}/retire', [ScheduleController::class, 'retire'])->whereNumber('schedule')->name('church.schedules.retire');

Route::put('/duplicates/{pair}', [DuplicateController::class, 'resolve'])->whereNumber('pair')->name('church.duplicates.resolve');

Route::get('/poster', [PosterController::class, 'show'])->name('church.poster');
