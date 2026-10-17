<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Enums\RelationshipEnum;
use App\Foundation\Attributes\NonClientFacing;

#[NonClientFacing]
final readonly class GuardianDetailsData
{
    /** @param list<string> $ministries */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public RelationshipEnum $relationship,
        public ?string $phone,
        public string $email,
        public array $ministries = [],
    ) {}
}
