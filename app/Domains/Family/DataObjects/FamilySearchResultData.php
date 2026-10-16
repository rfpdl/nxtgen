<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Family;
use App\Foundation\Support\EmailMask;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A church-wide search hit when registering for a parent (plan §7.2) — enough to say "already registered"
 * and add a child, nothing more: masked email, no phone, no health details.
 */
#[TypeScript]
final class FamilySearchResultData extends Data
{
    /** @param list<string> $children */
    public function __construct(
        public int $id,
        public string $primary_name,
        public string $masked_email,
        public bool $email_confirmed,
        public string $satellite,
        public array $children,
        public bool $can_open,
    ) {}

    public static function fromModel(Family $family, bool $canOpen): self
    {

        return new self(
            id: $family->id,
            primary_name: $family->contactName(),
            masked_email: EmailMask::mask($family->contactEmail()),
            email_confirmed: $family->contactEmailConfirmed(),
            satellite: $family->homeSatellite->name,
            children: array_values($family->children->map(fn ($c): string => $c->nickname)->all()),
            can_open: $canOpen,
        );
    }
}
