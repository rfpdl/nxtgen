<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Foundation\Attributes\NonClientFacing;

#[NonClientFacing]
final readonly class ChildDetailsData
{
    public function __construct(
        public string $first_name,
        public ?string $middle_name,
        public string $last_name,
        public string $nickname,
        public GenderEnum $gender,
        public string $date_of_birth,
        public ?string $allergies,
        public ?string $illnesses = null,
        public ?string $dislikes = null,
        public ?string $fears = null,
        public ?string $guardian_notes = null,
        public ?ChildStatusEnum $status = null,
    ) {}
}
