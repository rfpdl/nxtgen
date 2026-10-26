<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Schedule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A class in Attendance's picker: an active one, or a retired one kept for its Sessions (shown "1PM (retired)"). */
#[TypeScript]
final class AttendanceClassData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $retired,
    ) {}

    public static function fromModel(Schedule $schedule): self
    {
        return new self(id: $schedule->id, name: $schedule->name, retired: ! $schedule->is_active);
    }
}
