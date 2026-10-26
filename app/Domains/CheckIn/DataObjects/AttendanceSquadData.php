<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\CheckIn;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One Squad on the Attendance page: "Level 2 Girls", how many of its children are in, and who. */
#[TypeScript]
final class AttendanceSquadData extends Data
{
    /** @param list<AttendanceChildData> $children */
    public function __construct(
        public string $name,
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
        $groups = [];
        foreach ($checkIns as $checkIn) {
            $groups[$checkIn->squad_id]['name'] = $checkIn->squad->name();
            $groups[$checkIn->squad_id]['children'][] = AttendanceChildData::fromCheckIn($checkIn, $timezone);
        }

        return array_values(array_map(
            fn (array $g): self => new self(name: $g['name'], count: count($g['children']), children: $g['children']),
            $groups,
        ));
    }
}
