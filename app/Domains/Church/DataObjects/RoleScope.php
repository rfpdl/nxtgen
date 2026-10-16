<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Enums\ScopeTypeEnum;

/**
 * Where a Role is held (GLOSSARY "Role"): the whole Church or one Satellite — the ONE shape for giving, removing,
 * naming and checking a role. Schedule roles (shown as "class", Release 2) are added here: `of()` and `churchIdOf()`
 * learn the Schedule model, and nothing else changes.
 */
final readonly class RoleScope
{
    public function __construct(
        public ScopeTypeEnum $type,
        public int $id,
    ) {}

    /** The place a role is given at. */
    public static function of(Church|Satellite $place): self
    {
        return $place instanceof Satellite
            ? new self(ScopeTypeEnum::Satellite, $place->id)
            : new self(ScopeTypeEnum::Church, $place->id);
    }

    /** The church a role at this place belongs to. */
    public static function churchIdOf(Church|Satellite $place): int
    {
        return $place instanceof Satellite ? $place->church_id : $place->id;
    }

    /** Where a held (or removed) role is. */
    public static function ofAssignment(RoleAssignment $assignment): self
    {
        return new self($assignment->scope_type, $assignment->scope_id);
    }
}
