<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Satellite;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SatelliteOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    public static function fromModel(Satellite $satellite): self
    {
        return new self(id: $satellite->id, name: $satellite->name);
    }
}
