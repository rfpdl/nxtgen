<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Child;
use App\Enums\GenderEnum;
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
        public string $first_name,
        public ?string $middle_name,
        public string $last_name,
        public string $nickname,
        public GenderEnum $gender,
        public string $date_of_birth,
        public ?string $allergies,
        public ?string $illnesses,
        public ?string $dislikes,
        public ?string $fears,
        public ?string $guardian_notes,
        public ?string $level,
        public string $status_label,
        public bool $photo_consent,
        public ?string $photo_url,
    ) {}

    public static function fromModel(Child $child): self
    {
        return new self(
            id: $child->id,
            first_name: $child->first_name,
            middle_name: $child->middle_name,
            last_name: $child->last_name,
            nickname: $child->nickname,
            gender: $child->gender,
            date_of_birth: $child->date_of_birth->toDateString(),
            allergies: $child->allergies,
            illnesses: $child->illnesses,
            dislikes: $child->dislikes,
            fears: $child->fears,
            guardian_notes: $child->guardian_notes,
            level: $child->levelName(),
            status_label: $child->status->label(),
            photo_consent: $child->photo_consent_at !== null,
            photo_url: $child->photo_path === null || $child->photo_consent_at === null ? null
                : URL::temporarySignedRoute('photos.child', now()->addMinutes(30), ['child' => $child->id, 'v' => md5($child->photo_path)]),
        );
    }
}
