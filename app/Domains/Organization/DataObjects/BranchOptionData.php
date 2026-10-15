<?php

declare(strict_types=1);

namespace App\Domains\Organization\DataObjects;

use App\Data\Models\Branch;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class BranchOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    public static function fromModel(Branch $branch): self
    {
        return new self(id: $branch->id, name: $branch->name);
    }
}
