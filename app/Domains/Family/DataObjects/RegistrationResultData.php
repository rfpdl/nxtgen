<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Family;
use App\Enums\RegistrationOutcomeEnum;
use App\Foundation\Attributes\NonClientFacing;

#[NonClientFacing]
final readonly class RegistrationResultData
{
    public function __construct(
        public RegistrationOutcomeEnum $outcome,
        public ?Family $family,
    ) {}
}
