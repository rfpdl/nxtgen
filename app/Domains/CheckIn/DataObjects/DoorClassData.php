<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Schedule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One of today's classes in the door's picker: "1PM", "13:00–15:00" — the satellite is the bar's (spec item 13). */
#[TypeScript]
final class DoorClassData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        /** "13:00–15:00" */
        public string $times,
    ) {}

    public static function fromModel(Schedule $schedule): self
    {
        return new self(
            id: $schedule->id,
            name: $schedule->name,
            times: "{$schedule->startsAt()}–{$schedule->endsAt()}",
        );
    }
}
