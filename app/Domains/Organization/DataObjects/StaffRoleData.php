<?php

declare(strict_types=1);

namespace App\Domains\Organization\DataObjects;

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
        public bool $canRemove,
    ) {}

    /** @param array<int, string> $branchNames id => name, this church's satellites */
    public static function fromModel(RoleAssignment $assignment, array $branchNames, bool $canRemove): self
    {
        return new self(
            id: $assignment->id,
            label: self::label($assignment->role, $assignment->scope_type, $branchNames[$assignment->scope_id] ?? null),
            canRemove: $canRemove,
        );
    }

    /** The ONE wording for a role, used by the list, the "give a role" choices and the invite email. */
    public static function label(RoleEnum $role, ScopeTypeEnum $type, ?string $branchName): string
    {
        $where = match ($type) {
            ScopeTypeEnum::Organization => 'whole church',
            ScopeTypeEnum::Branch => $branchName ?? 'a satellite',
            ScopeTypeEnum::EventSeries => 'a class',
        };

        return "{$role->label()} · {$where}";
    }
}
