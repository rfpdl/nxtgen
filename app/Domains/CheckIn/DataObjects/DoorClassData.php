<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Schedule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One of today's classes in the door's picker: "1PM · CCF DXB · 13:00–15:00". Needs the class's satellite loaded. */
#[TypeScript]
final class DoorClassData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $satellite,
        /** "13:00–15:00", "from 13:00", or null when the class has no time. */
        public ?string $times,
    ) {}

    public static function fromModel(Schedule $schedule): self
    {
        $start = $schedule->startsAt();
        $end = $schedule->endsAt();

        return new self(
            id: $schedule->id,
            name: $schedule->name,
            satellite: $schedule->satellite->name,
            times: match (true) {
                $start !== null && $end !== null => "{$start}–{$end}",
                $start !== null => "from {$start}",
                default => null,
            },
        );
    }
}
