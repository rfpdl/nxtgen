<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Child;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child's health details at the door — sent ONLY when Staff tap the child's name (the door's search carries an
 * allergy yes/no and nothing more). The child may be visiting from another satellite: the Lifeshaper looking after
 * them today needs to know.
 */
#[TypeScript]
final class ChildHealthData extends Data
{
    public function __construct(
        public int $id,
        public string $nickname,
        public ?string $allergies,
        public ?string $illnesses,
        public ?string $dislikes,
        public ?string $fears,
        public ?string $guardian_notes,
    ) {}

    public static function fromModel(Child $child): self
    {
        return new self(
            id: $child->id,
            nickname: $child->displayName(),
            allergies: $child->allergies,
            illnesses: $child->illnesses,
            dislikes: $child->dislikes,
            fears: $child->fears,
            guardian_notes: $child->guardian_notes,
        );
    }
}
