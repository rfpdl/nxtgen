<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\CheckIn\DataObjects\AttendanceClassData;
use App\Domains\CheckIn\DataObjects\AttendanceSquadData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Attendance: one class on one date, its Squads and the total (Church/Attendance/Index) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class AttendancePageData extends Data
{
    public function __construct(
        /** @var list<AttendanceClassData> */
        public array $classes,
        /** the class shown; null when none */
        public ?int $class_id,
        public string $date,
        public string $date_label,
        /** @var list<AttendanceSquadData> */
        public array $squads,
        public int $total,
    ) {}
}
