<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Door;

use App\Services\Church\Features\Door\CheckInChildrenFeature;
use App\Services\Church\Features\Door\ConfirmHeadCountFeature;
use App\Services\Church\Features\Door\MarkPresentFeature;
use App\Services\Church\Features\Door\ShowDoorFeature;
use App\Services\Church\Features\Door\ShowWhosInFeature;
use App\Services\Church\Features\Door\UndoCheckInFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * The door: today's classes, the family search, checking children in and undoing it (DECISIONS 2026-10-26); and its
 * Who's in tab, where a Squad leader marks children present and confirms a head count (DECISIONS 2026-10-28).
 */
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

    public function whosIn(): Response
    {
        return $this->serve(ShowWhosInFeature::class);
    }

    public function present(string $church, int $checkIn): RedirectResponse
    {
        return $this->serve(MarkPresentFeature::class, ['checkInId' => $checkIn]);
    }

    public function headCount(string $church, int $schedule, int $squad): RedirectResponse
    {
        return $this->serve(ConfirmHeadCountFeature::class, ['scheduleId' => $schedule, 'squadId' => $squad]);
    }
}
