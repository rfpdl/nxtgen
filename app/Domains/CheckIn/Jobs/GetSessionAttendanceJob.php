<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;

/**
 * The children checked in to a Session (undone ones left out), for the Attendance report, its Excel and Who's in: each
 * Check-in with its child, Squad and who checked them in — by Squad (Level, then the mixed Squad, boys, girls), then
 * nickname.
 *
 * @extends BaseJob<list<CheckIn>>
 */
final class GetSessionAttendanceJob extends BaseJob
{
    public function __construct(private readonly Session $session) {}

    /** @return list<CheckIn> */
    public function handle(): array
    {
        return array_values(CheckIn::query()
            ->where('church_id', $this->session->church_id)
            ->where('session_id', $this->session->id)
            ->whereNull('undone_at')
            ->with(['child', 'squad', 'checkedInBy'])
            ->get()
            ->sortBy([
                fn (CheckIn $a, CheckIn $b): int => $a->squad->rank() <=> $b->squad->rank(),
                fn (CheckIn $a, CheckIn $b): int => mb_strtolower($a->child->nickname) <=> mb_strtolower($b->child->nickname),
                fn (CheckIn $a, CheckIn $b): int => $a->id <=> $b->id,
            ])
            ->all());
    }
}
