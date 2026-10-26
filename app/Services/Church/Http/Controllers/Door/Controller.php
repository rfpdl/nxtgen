<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Door;

use App\Services\Church\Features\Door\CheckInChildrenFeature;
use App\Services\Church\Features\Door\ShowDoorFeature;
use App\Services\Church\Features\Door\UndoCheckInFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** The door: today's classes, the family search, checking children in and undoing it (DECISIONS 2026-10-26). */
final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowDoorFeature::class);
    }

    public function checkIn(string $church, int $schedule): RedirectResponse
    {
        return $this->serve(CheckInChildrenFeature::class, ['scheduleId' => $schedule]);
    }

    public function undo(string $church, int $checkIn): RedirectResponse
    {
        return $this->serve(UndoCheckInFeature::class, ['checkInId' => $checkIn]);
    }
}
