<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A Link on the poster: its label, its address in words, and its QR code as an image (a data URI, never markup). */
#[TypeScript]
final class PosterLinkData extends Data
{
    public function __construct(
        public string $label,
        public string $url,
        public string $qr,
    ) {}
}
