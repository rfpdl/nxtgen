<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Child;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A parent's own child, including health details (visible to staff and the child's own family only). */
#[TypeScript]
final class ChildPortalData extends Data
{
    public function __construct(
        public int $id,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public string $nickname,
        public string $gender,
        public string $dateOfBirth,
        public ?string $allergies,
        public ?string $illnesses,
        public ?string $dislikes,
        public ?string $fears,
        public ?string $parentNotes,
        public ?string $group,
        public string $status,
    ) {}

    public static function fromModel(Child $child): self
    {
        return new self(
            id: $child->id,
            firstName: $child->first_name,
            middleName: $child->middle_name,
            lastName: $child->last_name,
            nickname: $child->nickname,
            gender: $child->gender->value,
            dateOfBirth: $child->date_of_birth->toDateString(),
            allergies: $child->allergies,
            illnesses: $child->illnesses,
            dislikes: $child->dislikes,
            fears: $child->fears,
            parentNotes: $child->parent_notes,
            group: $child->group?->name,
            status: $child->status->label(),
        );
    }
}
