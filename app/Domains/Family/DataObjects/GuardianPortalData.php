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
        public string $firstName,
        public string $lastName,
        public string $relationship,
        public ?string $phone,
    ) {}

    public static function fromModel(Guardian $guardian): self
    {
        return new self(
            firstName: $guardian->first_name,
            lastName: $guardian->last_name,
            relationship: $guardian->relationship->label(),
            phone: $guardian->phone,
        );
    }
}
