<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\Church\DataObjects\SatelliteLinksData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Links: each satellite's poster links, and how many it may have (Church/Links/Index) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class LinksPageData extends Data
{
    public function __construct(
        /** @var list<SatelliteLinksData> */
        public array $satellites,
        public int $max,
    ) {}
}
