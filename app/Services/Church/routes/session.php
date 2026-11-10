<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Church — an opened class's own address (prefix /{session}, RequireSessionAssignment)
|--------------------------------------------------------------------------
|
| Spec item 16: opening a class gives its Session a code, and its door and Who's in live at that code —
| `/<code>/checkin`, `/<code>/whos-in` — for anyone with a Role at its satellite covering the class. What each may do
| there is still AccessScope's; what changes needs the Session open (spec item 15). Ids are looked up within its church.
*/

use App\Services\Church\Http\Controllers\Door\Controller as DoorController;
use Illuminate\Support\Facades\Route;

Route::name('session.')->group(function (): void {
    // The door (DECISIONS 2026-10-26): find a family, check children in; undo a mistake.
    Route::get('/checkin', [DoorController::class, 'show'])->name('checkin');
    Route::post('/check-ins', [DoorController::class, 'checkIn'])->name('check-ins.store');
    Route::put('/check-ins/{checkIn}/undo', [DoorController::class, 'undo'])->whereNumber('checkIn')->name('undo');
    // Who's in (DECISIONS 2026-10-28 — roll call): by Squad; tap a child present; confirm a head count.
    Route::get('/whos-in', [DoorController::class, 'whosIn'])->name('whos-in');
    Route::put('/check-ins/{checkIn}/present', [DoorController::class, 'present'])->whereNumber('checkIn')->name('present');
    Route::put('/squads/{squad}/head-count', [DoorController::class, 'headCount'])->whereNumber('squad')->name('head-count');
    // An admin closes it (spec item 15); it is opened from Serve (serve.sessions.open).
    Route::put('/close', [DoorController::class, 'close'])->name('close');
});
