<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What happened to one child when Staff checked them in at the door (DECISIONS 2026-10-26): in (or already in), or
 * refused — Graduated children are no longer part of NxtGen, an inactive child is made active again first (the door
 * asks), and a child with no Level gets one picked first.
 */
#[TypeScript]
enum CheckInResultEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case CheckedIn = 'checked_in';
    case AlreadyIn = 'already_in';
    case Graduated = 'graduated';
    case Inactive = 'inactive';
    case NeedsLevel = 'needs_level';

    /** After it, the child is in the Session. */
    public function isIn(): bool
    {
        return $this === self::CheckedIn || $this === self::AlreadyIn;
    }

    /** Said after the child's name: "Ben is checked in", "Noah has Graduated from NxtGen". */
    public function label(): string
    {
        return match ($this) {
            self::CheckedIn => 'is checked in',
            self::AlreadyIn => 'was already checked in',
            self::Graduated => 'has Graduated from NxtGen',
            self::Inactive => 'is inactive — make them active again first',
            self::NeedsLevel => 'needs a Level first',
        };
    }

    public function color(): string
    {
        return $this->isIn() ? 'success' : 'danger';
    }
}
