<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\SatelliteLink;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A Link as the Links page shows and edits it. */
#[TypeScript]
final class SatelliteLinkData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
        public string $url,
    ) {}

    public static function fromModel(SatelliteLink $link): self
    {
        return new self(id: $link->id, label: $link->label, url: $link->url);
    }
}
