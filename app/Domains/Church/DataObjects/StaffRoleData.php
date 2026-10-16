<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\RoleAssignment;
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

    /** @param array<int, string> $satelliteNames id => name, this church's satellites */
    public static function fromModel(RoleAssignment $assignment, array $satelliteNames, bool $canRemove): self
    {
        return new self(
            id: $assignment->id,
            label: self::label($assignment->role, $assignment->scope_type, $satelliteNames[$assignment->scope_id] ?? null),
            can_remove: $canRemove,
        );
    }

    /** The ONE wording for a role, used by the list, the "give a role" choices and the invite email. */
    public static function label(RoleEnum $role, ScopeTypeEnum $type, ?string $satelliteName): string
    {
        $where = match ($type) {
            ScopeTypeEnum::Church => 'whole church',
            ScopeTypeEnum::Satellite => $satelliteName ?? 'a satellite',
            ScopeTypeEnum::Schedule => 'a class',
        };

        return "{$role->label()} · {$where}";
    }
}
