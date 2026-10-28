<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\CheckIn;
use App\Foundation\DataObjects\LevelData;
use App\Foundation\Support\CheckInGroups;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One Squad on the Attendance page: "Level 2 Girls" (and its Level, for the pill's colour), how many of its children are in, and who. */
#[TypeScript]
final class AttendanceSquadData extends Data
{
    /** @param list<AttendanceChildData> $children */
    public function __construct(
        public string $name,
        /** The Squad's Level: its number picks the pill's colour from the age-level chart. */
        public LevelData $level,
        public int $count,
        public array $children,
    ) {}

    /**
     * The Check-ins grouped by Squad, keeping their order (GetSessionAttendanceJob's: by Squad, then nickname).
     *
     * @param  list<CheckIn>  $checkIns
     * @return list<self>
     */
    public static function groupsOf(array $checkIns, string $timezone): array
    {
        return array_map(fn (array $squad): self => new self(
            name: $squad[0]->squad->name(),
            level: LevelData::of($squad[0]->squad->level),
            count: count($squad),
            children: array_map(fn (CheckIn $c): AttendanceChildData => AttendanceChildData::fromCheckIn($c, $timezone), $squad),
        ), CheckInGroups::bySquad($checkIns));
    }
}
