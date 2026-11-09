<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Satellite;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A satellite on the platform's list (spec item 12): its name, address, registration code and how many admins it has. */
#[TypeScript]
final class PlatformSatelliteData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $code,
        public int $admins,
    ) {}

    public static function fromModel(Satellite $satellite, int $admins): self
    {
        return new self(id: $satellite->id, name: $satellite->name, slug: $satellite->slug, code: $satellite->code, admins: $admins);
    }
}
