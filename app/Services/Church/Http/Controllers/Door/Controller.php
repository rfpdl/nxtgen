<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Door;

use App\Data\Models\CheckIn;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Services\Church\Features\Door\CheckInChildrenFeature;
use App\Services\Church\Features\Door\CloseSessionFeature;
use App\Services\Church\Features\Door\ConfirmHeadCountFeature;
use App\Services\Church\Features\Door\MarkPresentFeature;
use App\Services\Church\Features\Door\OpenSessionFeature;
use App\Services\Church\Features\Door\ShowDoorFeature;
use App\Services\Church\Features\Door\ShowWhosInFeature;
use App\Services\Church\Features\Door\UndoCheckInFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * A class's door, at its Session's own address (spec item 16: `/<code>/checkin`): the family search, checking children
 * in and undoing it (DECISIONS 2026-10-26), and its Who's in tab, where a Squad leader marks children present and
 * confirms a head count (DECISIONS 2026-10-28) — while an admin has it open, who also closes it (spec item 15). Opening
 * is from Serve, at the satellite's address.
 */
final class Controller extends \Lucid\Units\Controller
{
    public function open(Schedule $schedule): RedirectResponse
    {
        return $this->serve(OpenSessionFeature::class, ['schedule' => $schedule]);
    }

    public function close(Session $session): RedirectResponse
    {
        return $this->serve(CloseSessionFeature::class, ['session' => $session]);
    }

    public function show(Session $session): Response
    {
        return $this->serve(ShowDoorFeature::class, ['session' => $session]);
    }

    public function checkIn(Session $session): RedirectResponse
    {
        return $this->serve(CheckInChildrenFeature::class, ['session' => $session]);
    }

    public function undo(Session $session, CheckIn $checkIn): RedirectResponse
    {
        return $this->serve(UndoCheckInFeature::class, ['session' => $session, 'checkIn' => $checkIn]);
    }

    public function whosIn(Session $session): Response
    {
        return $this->serve(ShowWhosInFeature::class, ['session' => $session]);
    }

    public function present(Session $session, CheckIn $checkIn): RedirectResponse
    {
        return $this->serve(MarkPresentFeature::class, ['session' => $session, 'checkIn' => $checkIn]);
    }

    public function headCount(Session $session, int $squad): RedirectResponse
    {
        return $this->serve(ConfirmHeadCountFeature::class, ['session' => $session, 'squadId' => $squad]);
    }
}
