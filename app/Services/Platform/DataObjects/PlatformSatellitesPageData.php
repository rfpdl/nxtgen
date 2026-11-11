<?php

declare(strict_types=1);

namespace App\Services\Platform\DataObjects;

use App\Domains\Church\DataObjects\PlatformSatelliteData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The platform admin's satellites (Platform/Satellites) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class PlatformSatellitesPageData extends Data
{
    public function __construct(
        /** @var list<PlatformSatelliteData> */
        public array $satellites,
    ) {}
}
