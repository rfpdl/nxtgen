<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A satellite in Attendance's picker, with the classes that can be reported on (GetReportableSchedulesJob). */
#[TypeScript]
final class AttendanceSatelliteData extends Data
{
    /** @param list<AttendanceClassData> $classes */
    public function __construct(
        public int $id,
        public string $name,
        public array $classes,
    ) {}
}
