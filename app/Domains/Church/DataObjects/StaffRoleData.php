<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\RoleAssignment;
use App\Data\Models\Schedule;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One role a person holds, as the People page shows it: "Lifeshaper · Holy Trinity Church". */
#[TypeScript]
final class StaffRoleData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
        public bool $can_remove,
    ) {}

    /**
     * @param  array<int, string>  $satelliteNames  id => name, this church's satellites
     * @param  array<int, Schedule>  $classes  id => class, for class admins' roles (TODO #9)
     */
    public static function fromModel(RoleAssignment $assignment, array $satelliteNames, array $classes, bool $canRemove): self
    {
        $class = $assignment->scope_type === ScopeTypeEnum::Schedule ? $classes[$assignment->scope_id] ?? null : null;

        return new self(
            id: $assignment->id,
            label: self::label($assignment->role, RoleScope::ofAssignment($assignment, $class), $class === null ? $satelliteNames[$assignment->scope_id] ?? null : self::className($class)),
            can_remove: $canRemove,
        );
    }

    /** How People names a class: its satellite, then its name — "CCF DXB · 1PM". */
    public static function className(Schedule $class): string
    {
        return "{$class->satellite->name} · {$class->name}";
    }

    /** The ONE wording for a role, used by the list, the "give a role" choices and the invite email. `$placeName`: the satellite's, or the class's (className). */
    public static function label(RoleEnum $role, RoleScope $scope, ?string $placeName): string
    {
        $where = match ($scope->type) {
            ScopeTypeEnum::Satellite => $placeName ?? 'a satellite',
            ScopeTypeEnum::Schedule => $placeName ?? 'a class',
        };

        return "{$role->label()} · {$where}";
    }
}
