<?php

declare(strict_types=1);

namespace App\Domains\Schedule\DataObjects;

use App\Data\Models\Schedule;
use App\Enums\WeekdayEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One class (Schedule) on the Classes page: what it is called, and when it meets — the values its edit form starts from. */
#[TypeScript]
final class ScheduleData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?WeekdayEnum $weekday,
        /** "Saturdays"; null = any day. */
        public ?string $weekday_label,
        /** "13:00" */
        public ?string $starts_at,
        public ?string $ends_at,
    ) {}

    public static function fromModel(Schedule $schedule): self
    {
        return new self(
            id: $schedule->id,
            name: $schedule->name,
            weekday: $schedule->weekday,
            weekday_label: $schedule->weekday?->everyLabel(),
            starts_at: $schedule->startsAt(),
            ends_at: $schedule->endsAt(),
        );
    }
}
