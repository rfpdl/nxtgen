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
 * One Squad on Who's in: "Level 2 Girls" (and its Level, for the pill's colour), how many of its checked-in children
 * are marked present (`present` of `count`), and who; and its confirmed head count, if any (null = not yet). `id` is
 * the Squad's — what `?squad=` picks and a head count is confirmed for.
 */
#[TypeScript]
final class WhosInSquadData extends Data
{
    /** @param list<WhosInChildData> $children */
    public function __construct(
        public int $id,
        public string $name,
        /** The Squad's Level: its number picks the pill's colour from the age-level chart. */
        public LevelData $level,
        public int $present,
        public int $count,
        public array $children,
        public ?SquadHeadCountData $head_count,
    ) {}

    /**
     * The Check-ins grouped by Squad, keeping their order (GetSessionAttendanceJob's: by Squad, then nickname).
     *
     * @param  list<CheckIn>  $checkIns
     * @param  array<int, SquadHeadCount>  $headCounts  by Squad id (GetSessionHeadCountsJob)
     * @return list<self>
     */
    public static function groupsOf(array $checkIns, array $headCounts, string $timezone): array
    {
        return array_map(function (array $squad) use ($headCounts, $timezone): self {
            $headCount = $headCounts[$squad[0]->squad_id] ?? null;
            $children = array_map(fn (CheckIn $c): WhosInChildData => WhosInChildData::fromCheckIn($c), $squad);

            return new self(
                id: $squad[0]->squad_id,
                name: $squad[0]->squad->name(),
                level: LevelData::of($squad[0]->squad->level),
                present: count(array_filter($children, fn (WhosInChildData $c): bool => $c->present)),
                count: count($children),
                children: $children,
                head_count: $headCount === null ? null : SquadHeadCountData::fromModel($headCount, $timezone),
            );
        }, CheckInGroups::bySquad($checkIns));
    }
}
