<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\Schedule\DataObjects\SatelliteSchedulesData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Classes: each satellite this person looks after, with its classes (Church/Schedules/Index) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class SchedulesPageData extends Data
{
    public function __construct(
        /** @var list<SatelliteSchedulesData> */
        public array $satellites,
        /** @var array<string, list<array{value: string, label: string, color: string}>> the enums its forms read with useEnum() — page-wide, not a prop of the page */
        #[Hidden]
        public array $enums,
    ) {}
}
