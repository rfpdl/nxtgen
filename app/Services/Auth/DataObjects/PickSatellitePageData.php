<?php

declare(strict_types=1);

namespace App\Services\Auth\DataObjects;

use App\Domains\Church\DataObjects\SatelliteChoiceData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** "Pick your satellite", for staff of several who used none yet (Auth/PickSatellite) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class PickSatellitePageData extends Data
{
    public function __construct(
        /** @var list<SatelliteChoiceData> */
        public array $satellites,
    ) {}
}
