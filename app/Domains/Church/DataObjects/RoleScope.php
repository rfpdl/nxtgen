<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Enums\ScopeTypeEnum;

/**
 * Where a Role is held (GLOSSARY "Role"): the whole Church, one Satellite, or one class (a Schedule — class admin,
 * TODO #9) — the ONE shape for giving, removing, naming and checking a role. A class's scope carries its satellite,
 * which is what a satellite admin's right to give or remove it turns on (`satelliteId`; null = not known here).
 */
final readonly class RoleScope
{
    public function __construct(
        public ScopeTypeEnum $type,
        public int $id,
        public ?int $satelliteId = null,
    ) {}

    /** The place a role is given at. */
    public static function of(Church|Satellite|Schedule $place): self
    {
        return match (true) {
            $place instanceof Schedule => new self(ScopeTypeEnum::Schedule, $place->id, $place->satellite_id),
            $place instanceof Satellite => new self(ScopeTypeEnum::Satellite, $place->id),
            default => new self(ScopeTypeEnum::Church, $place->id),
        };
    }

    /** The church a role at this place belongs to. */
    public static function churchIdOf(Church|Satellite|Schedule $place): int
    {
        return $place instanceof Church ? $place->id : $place->church_id;
    }

    /** Where a held (or removed) role is — a class role with its class (`$class`, when the role is at one). */
    public static function ofAssignment(RoleAssignment $assignment, ?Schedule $class = null): self
    {
        return new self($assignment->scope_type, $assignment->scope_id, $class?->satellite_id);
    }
}
