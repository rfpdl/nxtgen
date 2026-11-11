<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\Church\DataObjects\PosterLinkData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A satellite's QR poster: its registration address and QR code, and its Links (Church/Poster) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class PosterPageData extends Data
{
    public function __construct(
        public string $register_url,
        /** what the QR code says: the satellite's code address, which never changes (spec item 7) */
        public string $qr_url,
        public string $qr,
        /** @var list<PosterLinkData> */
        public array $links,
    ) {}
}
