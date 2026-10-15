<?php

declare(strict_types=1);

namespace App\Domains\Group\DataObjects;

use App\Data\Models\Group;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class GroupOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    public static function fromModel(Group $group): self
    {
        return new self(id: $group->id, name: $group->name);
    }
}
