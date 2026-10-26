<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\Session;
use App\Enums\GenderEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * The children checked in to a Session (undone ones left out), for the Attendance report and its Excel: each
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
                fn (CheckIn $a, CheckIn $b): int => $a->squad->level <=> $b->squad->level,
                fn (CheckIn $a, CheckIn $b): int => self::genderRank($a) <=> self::genderRank($b),
                fn (CheckIn $a, CheckIn $b): int => mb_strtolower($a->child->nickname) <=> mb_strtolower($b->child->nickname),
                fn (CheckIn $a, CheckIn $b): int => $a->id <=> $b->id,
            ])
            ->all());
    }

    /** Within a Level: the mixed Squad, then boys, then girls — named, not left to the enum's spelling. */
    private static function genderRank(CheckIn $checkIn): int
    {
        return match ($checkIn->squad->gender) {
            null => 0,
            GenderEnum::Boy => 1,
            GenderEnum::Girl => 2,
        };
    }
}
