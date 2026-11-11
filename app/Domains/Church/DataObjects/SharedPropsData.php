<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What every page receives (HandleInertiaRequests::share) — ONE contract with the pages' `usePage<SharedProps>()`
 * (TODO #43). Most of it is worked out only when a page asks for it (`Lazy::closure`, so a partial reload that names
 * other props — the roll call's poll — skips the queries); each such field's type is the value it gives.
 */
#[TypeScript]
final class SharedPropsData extends Data
{
    public function __construct(
        public SharedAuthData $auth,
        /** The church the page is about: the address's satellite's, or a parent's own; null elsewhere. */
        #[LiteralTypeScriptType('ChurchData | null')]
        public Lazy|ChurchData|null $church,
        /** @var Lazy|list<SatelliteChoiceData> the bar's satellite switch: on a staff page, the satellites its viewer may open (spec item 13); else none */
        #[LiteralTypeScriptType('SatelliteChoiceData[]')]
        public Lazy|array $open_satellites,
        /** The satellite the address names (`/{satellite}/…`); null on a page outside one. */
        #[LiteralTypeScriptType('SatelliteData | null')]
        public Lazy|SatelliteData|null $satellite,
        /** The staff menu: set on church staff pages (from RequireAssignment's scope), null on every other page. */
        #[LiteralTypeScriptType('ChurchNavData | null')]
        public Lazy|ChurchNavData|null $church_nav,
        public SharedFlashData $flash,
    ) {}
}
