<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\CheckIn;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child checked in to the chosen class today, on Who's in (DECISIONS 2026-10-28): the nickname and first + last
 * name, the allergy flag as at the door (yes/no, never the detail) and whether they are marked present. No photo, no
 * phone. `id` is the Check-in's — what a tap marks. Needs the Check-in's child loaded.
 */
#[TypeScript]
final class WhosInChildData extends Data
{
    public function __construct(
        /** The Check-in's id — the row's key and what a tap marks present. */
        public int $id,
        public string $nickname,
        /** "Amara Reyes" — no middle name — small under the nickname, so two Amaras in a Squad differ (issue 04). */
        public string $short_name,
        public bool $has_allergies,
        public bool $present,
    ) {}

    public static function fromCheckIn(CheckIn $checkIn): self
    {
        return new self(
            id: $checkIn->id,
            nickname: $checkIn->child->displayName(),
            short_name: $checkIn->child->shortName(),
            has_allergies: $checkIn->child->hasAllergies(),
            present: $checkIn->isPresent(),
        );
    }
}
