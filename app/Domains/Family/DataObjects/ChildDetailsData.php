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
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public string $nickname,
        public GenderEnum $gender,
        public string $dateOfBirth,
        public ?string $allergies,
        public ?string $illnesses = null,
        public ?string $dislikes = null,
        public ?string $fears = null,
        public ?string $guardianNotes = null,
        public ?ChildStatusEnum $status = null,
    ) {}
}
