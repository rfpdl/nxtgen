<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Illuminate\Support\Str;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The day a Schedule (shown as "class") meets each week. Stored as the day's name, ordered as ISO 8601 (Monday 1 …
 * Sunday 7). A class with no day (null) meets any day.
 */
#[TypeScript]
enum WeekdayEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';
    case Sunday = 'sunday';

    /** ISO 8601's day number (Carbon's dayOfWeekIso): Monday 1 … Sunday 7. */
    public function isoNumber(): int
    {
        return match ($this) {
            self::Monday => 1,
            self::Tuesday => 2,
            self::Wednesday => 3,
            self::Thursday => 4,
            self::Friday => 5,
            self::Saturday => 6,
            self::Sunday => 7,
        };
    }

    /** "Saturday" — as the day picker lists it. */
    public function label(): string
    {
        return Str::ucfirst($this->value);
    }

    /** "Saturdays" — a class meets every one of them (the class line: "Saturdays · 13:00–15:00"). */
    public function everyLabel(): string
    {
        return $this->label().'s';
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
