<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Guardian;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class GuardianPortalData extends Data
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $relationship_label,
        public ?string $phone,
    ) {}

    public static function fromModel(Guardian $guardian): self
    {
        return new self(
            first_name: $guardian->first_name,
            last_name: $guardian->last_name,
            relationship_label: $guardian->relationship->label(),
            phone: $guardian->phone,
        );
    }
}
