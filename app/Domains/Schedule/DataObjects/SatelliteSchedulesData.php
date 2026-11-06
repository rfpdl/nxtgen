<?php

declare(strict_types=1);

namespace App\Domains\Schedule\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A satellite on the Classes page, with its active classes in the order of the week — and whether this person may add
 * one there (a class admin, TODO #9, sees only their own class and adds none).
 */
#[TypeScript]
final class SatelliteSchedulesData extends Data
{
    /** @param list<ScheduleData> $schedules */
    public function __construct(
        public int $id,
        public string $name,
        public array $schedules,
        public bool $can_add,
    ) {}
}
