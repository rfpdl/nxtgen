<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\CheckIn;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One line of a child's attendance on the staff family page (slice 3): "Sat 31 Oct · 1PM · CCF DXB · Level 2 Girls ·
 * Present ✓". Needs the Check-in's session (schedule, satellite) and squad loaded (GetFamilyCheckInsJob does).
 */
#[TypeScript]
final class ChildCheckInData extends Data
{
    public function __construct(
        /** The Check-in's id — the line's key. */
        public int $id,
        /** "Sat 31 Oct"; "Sat 27 Dec 2025" when not this year (the church's). */
        public string $date,
        public string $class,
        public string $satellite,
        public string $squad,
        /** Marked present at the roll call; false = not marked. */
        public bool $present,
    ) {}

    public static function fromCheckIn(CheckIn $checkIn, int $thisYear): self
    {
        $date = $checkIn->session->date;

        return new self(
            id: $checkIn->id,
            date: $date->format($date->year === $thisYear ? 'D j M' : 'D j M Y'),
            class: $checkIn->session->schedule->name,
            satellite: $checkIn->session->satellite->name,
            squad: $checkIn->squad->name(),
            present: $checkIn->isPresent(),
        );
    }
}
