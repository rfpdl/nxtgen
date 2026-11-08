<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Door;

use App\Data\Models\CheckIn;
use App\Data\Models\Schedule;
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

    public function checkIn(Schedule $schedule): RedirectResponse
    {
        return $this->serve(CheckInChildrenFeature::class, ['schedule' => $schedule]);
    }

    public function undo(CheckIn $checkIn): RedirectResponse
    {
        return $this->serve(UndoCheckInFeature::class, ['checkIn' => $checkIn]);
    }

    public function whosIn(): Response
    {
        return $this->serve(ShowWhosInFeature::class);
    }

    public function present(CheckIn $checkIn): RedirectResponse
    {
        return $this->serve(MarkPresentFeature::class, ['checkIn' => $checkIn]);
    }

    public function headCount(Schedule $schedule, int $squad): RedirectResponse
    {
        return $this->serve(ConfirmHeadCountFeature::class, ['schedule' => $schedule, 'squadId' => $squad]);
    }
}
