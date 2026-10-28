<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\CheckIn;
use App\Data\Models\SquadHeadCount;
use App\Foundation\DataObjects\LevelData;
use App\Foundation\Support\CheckInGroups;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One Squad on the Attendance page: "Level 2 Girls" (and its Level, for the pill's colour), how many of its children are
 * in, and who; and its confirmed head count, if any (roll call, DECISIONS 2026-10-28; null = not counted).
 */
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
        public ?SquadHeadCountData $head_count,
    ) {}

    /**
     * The Check-ins grouped by Squad, keeping their order within a Squad (nickname), plus each Squad counted at the roll
     * call with no one in now — every Squad in the Attendance order (Squad::rank()).
     *
     * @param  list<CheckIn>  $checkIns
     * @param  array<int, SquadHeadCount>  $headCounts  by Squad id (GetSessionHeadCountsJob)
     * @return list<self>
     */
    public static function groupsOf(array $checkIns, array $headCounts, string $timezone): array
    {
        $squads = [];   // squad id => [its rank, its row]
        foreach (CheckInGroups::bySquad($checkIns) as $in) {
            $squad = $in[0]->squad;
            $squads[$squad->id] = [$squad->rank(), new self(
                name: $squad->name(),
                level: LevelData::of($squad->level),
                count: count($in),
                children: array_map(fn (CheckIn $c): AttendanceChildData => AttendanceChildData::fromCheckIn($c, $timezone), $in),
                head_count: isset($headCounts[$squad->id]) ? SquadHeadCountData::fromModel($headCounts[$squad->id], $timezone) : null,
            )];
        }
        // A Squad counted at the roll call whose children were all undone since: still shown, "0 in", with its count.
        foreach ($headCounts as $squadId => $headCount) {
            $squads[$squadId] ??= [$headCount->squad->rank(), new self(
                name: $headCount->squad->name(),
                level: LevelData::of($headCount->squad->level),
                count: 0,
                children: [],
                head_count: SquadHeadCountData::fromModel($headCount, $timezone),
            )];
        }
        uasort($squads, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return array_values(array_map(fn (array $squad): self => $squad[1], $squads));
    }
}
