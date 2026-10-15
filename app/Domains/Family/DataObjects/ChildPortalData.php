<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Child;
use Illuminate\Support\Facades\URL;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A parent's own child, including health details and the photo (visible to staff who can see the family and the
 * child's own family only — never in the door search's lite view). The photo is a short-lived signed link; the
 * photo route still checks who is asking (plan §3.3).
 */
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
        public bool $photoConsent,
        public ?string $photoUrl,
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
            photoConsent: $child->photo_consent_at !== null,
            photoUrl: $child->photo_path === null ? null
                : URL::temporarySignedRoute('photos.child', now()->addMinutes(30), ['child' => $child->id, 'v' => md5($child->photo_path)]),
        );
    }
}
