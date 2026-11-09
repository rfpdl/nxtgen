<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Satellite;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A satellite to go to — "Pick your satellite" and the bar's switch (spec items 11, 13): its name and its address. */
#[TypeScript]
final class SatelliteChoiceData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {}

    public static function fromModel(Satellite $satellite): self
    {
        return new self(name: $satellite->name, slug: $satellite->slug);
    }
}
