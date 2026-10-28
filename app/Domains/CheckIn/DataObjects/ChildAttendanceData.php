<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child's attendance on the staff family page (slice 3, DECISIONS 2026-10-28 night): every Check-in that was not
 * undone, newest first — staff only (a parent's own page never carries it). An empty list = "Not checked in yet".
 */
#[TypeScript]
final class ChildAttendanceData extends Data
{
    /** @param list<ChildCheckInData> $check_ins */
    public function __construct(
        public int $child_id,
        public array $check_ins,
    ) {}

    /**
     * One per child, in the family's order, each with its Check-ins in the order given (GetFamilyCheckInsJob's).
     *
     * @param  iterable<Child>  $children
     * @param  list<CheckIn>  $checkIns
     * @return list<self>
     */
    public static function forChildren(iterable $children, array $checkIns, int $thisYear): array
    {
        $byChild = [];
        foreach ($checkIns as $checkIn) {
            $byChild[$checkIn->child_id][] = ChildCheckInData::fromCheckIn($checkIn, $thisYear);
        }

        $all = [];
        foreach ($children as $child) {
            $all[] = new self(child_id: $child->id, check_ins: $byChild[$child->id] ?? []);
        }

        return $all;
    }
}
