<?php

declare(strict_types=1);

namespace App\Domains\Schedule\DataObjects;

use App\Data\Models\Schedule;
use App\Enums\WeekdayEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One class (Schedule) on the Classes page: what it is called, and when it meets — weekly or on one date — the values
 * its edit form starts from. */
#[TypeScript]
final class ScheduleData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        /** A weekly class's day; null for a one-time class. */
        public ?WeekdayEnum $weekday,
        /** "Saturdays"; null for a one-time class. */
        public ?string $weekday_label,
        /** A one-time class's date, "2026-12-24"; null for a weekly class. */
        public ?string $date,
        /** "Thu 24 Dec 2026" */
        public ?string $date_label,
        /** "13:00" */
        public string $starts_at,
        public string $ends_at,
    ) {}

    public static function fromModel(Schedule $schedule): self
    {
        return new self(
            id: $schedule->id,
            name: $schedule->name,
            weekday: $schedule->weekday,
            weekday_label: $schedule->weekday?->everyLabel(),
            date: $schedule->date?->toDateString(),
            date_label: $schedule->date?->format('D j M Y'),
            starts_at: $schedule->startsAt(),
            ends_at: $schedule->endsAt(),
        );
    }
}
