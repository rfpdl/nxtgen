<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Church — the church's staff pages (prefix /{church}, RequireAssignment)
|--------------------------------------------------------------------------
|
| By area (Roy 2026-11-06, tasks/2026-11-06-admin-serve): /{church}/admin (church-wide: People), and the ministry's
| two — /{church}/nxtgen/admin (managing families, children, classes, cards…) and /{church}/nxtgen/serve (the
| everyday app: the door, registering). Every admin address is 404 for anyone without an admin Role
| (RequireAdminArea); what each person may do inside is still AccessScope's (plan §3.2), never the URL's. An action
| is under each area whose pages use it, so a page never leaves its area; route names follow the address. Ids in
| the URL are looked up within this church only.
*/

use App\Services\Church\Http\Controllers\Attendance\Controller as AttendanceController;
use App\Services\Church\Http\Controllers\CardRequest\Controller as CardRequestController;
use App\Services\Church\Http\Controllers\Child\Controller as ChildController;
use App\Services\Church\Http\Controllers\Door\Controller as DoorController;
use App\Services\Church\Http\Controllers\Duplicate\Controller as DuplicateController;
use App\Services\Church\Http\Controllers\Family\Controller as FamilyController;
use App\Services\Church\Http\Controllers\Home\Controller as HomeController;
use App\Services\Church\Http\Controllers\Link\Controller as LinkController;
use App\Services\Church\Http\Controllers\MovingUp\Controller as MovingUpController;
use App\Services\Church\Http\Controllers\People\Controller as PeopleController;
use App\Services\Church\Http\Controllers\Poster\Controller as PosterController;
use App\Services\Church\Http\Controllers\Schedule\Controller as ScheduleController;
use App\Services\Church\Http\Middleware\RequireAdminArea;
use Illuminate\Support\Facades\Route;

// The person's area: Admin for an admin, else Serve.
Route::get('/', [HomeController::class, 'show'])->name('church.home');

/** The family pages and actions both areas use: registering, the list, one family, its children's details. */
$familyPages = function (bool $withExport): void {
    Route::get('/families', [FamilyController::class, 'index'])->name('families.index');
    if ($withExport) {
        Route::get('/families/export', [FamilyController::class, 'export'])->name('families.export');
    }
    Route::get('/families/new', [FamilyController::class, 'register'])->name('families.register');
    Route::post('/families', [FamilyController::class, 'store'])->name('families.store');
    Route::get('/families/{family}', [FamilyController::class, 'show'])->whereNumber('family')->name('families.show');
    Route::put('/families/{family}', [FamilyController::class, 'update'])->whereNumber('family')->name('families.update');
    Route::post('/families/{family}/children', [FamilyController::class, 'addChild'])->whereNumber('family')->name('families.children.store');
    Route::post('/families/{family}/resend', [FamilyController::class, 'resend'])->whereNumber('family')->middleware('throttle:10,1')->name('families.resend');
    Route::put('/children/{child}', [ChildController::class, 'update'])->whereNumber('child')->name('children.update');
    Route::put('/children/{child}/photo-consent', [ChildController::class, 'recordPhotoConsent'])->whereNumber('child')->name('children.photo-consent');
    Route::post('/children/{child}/photo', [ChildController::class, 'addPhoto'])->whereNumber('child')->name('children.photo');
    // Name cards (DECISIONS 2026-10-29): staff who may open the family ask for a new card (lost or damaged), and give a
    // printed one at the desk.
    Route::post('/children/{child}/card-requests', [CardRequestController::class, 'store'])->whereNumber('child')->name('card-requests.store');
    Route::put('/card-requests/{cardRequest}/give', [CardRequestController::class, 'give'])->whereNumber('cardRequest')->name('card-requests.give');
};

// ── Church-wide admin ────────────────────────────────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(RequireAdminArea::class)->group(function (): void {
    Route::get('/people', [PeopleController::class, 'index'])->name('people.index');
    Route::post('/people', [PeopleController::class, 'store'])->middleware('throttle:per-device')->name('people.store');
    Route::delete('/people/roles/{assignment}', [PeopleController::class, 'removeRole'])->whereNumber('assignment')->name('people.roles.destroy');
});

// ── NxtGen Admin ─────────────────────────────────────────────────────────────────────────────────────────────────
Route::prefix('nxtgen/admin')->name('nxtgen.admin.')->group(function () use ($familyPages): void {
    // A class admin (TODO #9) opens only these two in Admin: their class, and its Attendance.
    Route::middleware(RequireAdminArea::class.':class')->group(function (): void {
        // Classes (GLOSSARY "Schedule"): admins make a satellite's classes (DECISIONS 2026-10-26).
        Route::get('/classes', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/classes', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/classes/{schedule}', [ScheduleController::class, 'update'])->whereNumber('schedule')->name('schedules.update');
        Route::put('/classes/{schedule}/retire', [ScheduleController::class, 'retire'])->whereNumber('schedule')->name('schedules.retire');

        // Attendance (DECISIONS 2026-10-26: reports are admins only): ?satellite=&class=&date= picks the Session.
        Route::get('/attendance', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
    });

    Route::middleware(RequireAdminArea::class)->group(function () use ($familyPages): void {
        $familyPages(true);
        Route::put('/families/{family}/email', [FamilyController::class, 'correctEmail'])->whereNumber('family')->name('families.email');
        Route::get('/children', [ChildController::class, 'index'])->name('children.index');
        Route::put('/children/{child}/level', [ChildController::class, 'updateLevel'])->whereNumber('child')->name('children.level');
        Route::put('/children/{child}/status', [ChildController::class, 'updateStatus'])->whereNumber('child')->name('children.status');
        // A photo deleted for good (TODO #25): a parent asking for erasure — an admin who may edit the family.
        Route::delete('/children/{child}/photo', [ChildController::class, 'deletePhoto'])->whereNumber('child')->name('children.photo.destroy');
        Route::put('/duplicates/{pair}', [DuplicateController::class, 'resolve'])->whereNumber('pair')->name('duplicates.resolve');

        // Cards (name cards 03): admins see and move the requests by step. Print lists (name cards 04, GLOSSARY "Print
        // list"): the approved cards the tab shows, kept; downloaded, marked printed.
        Route::get('/card-requests', [CardRequestController::class, 'index'])->name('card-requests.index');
        Route::post('/card-requests/print-lists', [CardRequestController::class, 'makePrintList'])->name('card-requests.print-lists.store');
        Route::get('/card-requests/print-lists/{printList}', [CardRequestController::class, 'downloadPrintList'])->whereNumber('printList')->name('card-requests.print-lists.show');
        Route::put('/card-requests/print-lists/{printList}/mark-printed', [CardRequestController::class, 'markPrintListPrinted'])->whereNumber('printList')->name('card-requests.print-lists.mark-printed');
        Route::put('/card-requests/{cardRequest}/mark-paid', [CardRequestController::class, 'markPaid'])->whereNumber('cardRequest')->name('card-requests.mark-paid');
        Route::put('/card-requests/{cardRequest}/approve', [CardRequestController::class, 'approve'])->whereNumber('cardRequest')->name('card-requests.approve');
        Route::put('/card-requests/{cardRequest}/refuse', [CardRequestController::class, 'refuse'])->whereNumber('cardRequest')->name('card-requests.refuse');
        Route::put('/card-requests/{cardRequest}/mark-printed', [CardRequestController::class, 'markPrinted'])->whereNumber('cardRequest')->name('card-requests.mark-printed');

        // Moving-Up (TODO #27): the yearly step to the next Level, and putting the last one back — church admin only.
        Route::get('/moving-up', [MovingUpController::class, 'index'])->name('moving-up.index');
        Route::post('/moving-up', [MovingUpController::class, 'store'])->name('moving-up.store');
        Route::put('/moving-up/{movingUp}/children/{child}/put-back', [MovingUpController::class, 'putBackChild'])->whereNumber(['movingUp', 'child'])->name('moving-up.children.put-back');
        Route::put('/moving-up/{movingUp}/put-back', [MovingUpController::class, 'putBackEveryone'])->whereNumber('movingUp')->name('moving-up.put-back');

        // Links (GLOSSARY; Roy 2026-11-06): a satellite's own addresses on its QR poster.
        Route::get('/links', [LinkController::class, 'index'])->name('links.index');
        Route::post('/links', [LinkController::class, 'store'])->name('links.store');
        Route::put('/links/{link}', [LinkController::class, 'update'])->whereNumber('link')->name('links.update');
        Route::put('/links/{link}/move', [LinkController::class, 'move'])->whereNumber('link')->name('links.move');
        Route::delete('/links/{link}', [LinkController::class, 'destroy'])->whereNumber('link')->name('links.destroy');
    });
});

// ── NxtGen Serve ─────────────────────────────────────────────────────────────────────────────────────────────────
Route::prefix('nxtgen/serve')->name('nxtgen.serve.')->group(function () use ($familyPages): void {
    // The door (DECISIONS 2026-10-26): pick today's class, find a family, check children in; undo a mistake.
    Route::get('/door', [DoorController::class, 'show'])->name('door.show');
    Route::post('/classes/{schedule}/check-ins', [DoorController::class, 'checkIn'])->whereNumber('schedule')->name('door.check-in');
    Route::put('/check-ins/{checkIn}/undo', [DoorController::class, 'undo'])->whereNumber('checkIn')->name('door.undo');
    // Its Who's in tab (DECISIONS 2026-10-28 — roll call): today's class by Squad; tap a child present; confirm a head count.
    Route::get('/door/whos-in', [DoorController::class, 'whosIn'])->name('door.whos-in');
    Route::put('/check-ins/{checkIn}/present', [DoorController::class, 'present'])->whereNumber('checkIn')->name('door.present');
    Route::put('/classes/{schedule}/squads/{squad}/head-count', [DoorController::class, 'headCount'])->whereNumber(['schedule', 'squad'])->name('door.head-count');

    $familyPages(false);

    Route::get('/poster', [PosterController::class, 'show'])->name('poster');
});
