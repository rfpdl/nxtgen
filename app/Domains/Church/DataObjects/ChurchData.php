<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Church;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The church a page is about — shared with every page whose address names one (HandleInertiaRequests), so no
 * Feature builds it and no page declares it.
 */
#[TypeScript]
final class ChurchData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {}

    public static function fromModel(Church $church): self
    {
        return new self(name: $church->name, slug: $church->slug);
    }
}
