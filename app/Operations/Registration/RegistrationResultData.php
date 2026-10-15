<?php

declare(strict_types=1);

namespace App\Operations\Registration;

use App\Data\Models\Family;
use App\Enums\RegistrationOutcomeEnum;

final readonly class RegistrationResultData
{
    public function __construct(
        public RegistrationOutcomeEnum $outcome,
        public ?Family $family,
    ) {}
}
