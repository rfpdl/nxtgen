<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A satellite on the Links page, with its Links in the poster's order. */
#[TypeScript]
final class SatelliteLinksData extends Data
{
    /** @param list<SatelliteLinkData> $links */
    public function __construct(
        public int $id,
        public string $name,
        public array $links,
    ) {}
}
