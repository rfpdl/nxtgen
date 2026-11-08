<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Satellite;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The satellite a page is about — the one its address names (`/{satellite}/…`, Roy 2026-11-07) — shared with every
 * staff and registration page (HandleInertiaRequests), so no Feature builds it and no page declares it. Pages build
 * their links from its slug.
 */
#[TypeScript]
final class SatelliteData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
    ) {}

    public static function fromModel(Satellite $satellite): self
    {
        return new self(id: $satellite->id, name: $satellite->name, slug: $satellite->slug);
    }
}
