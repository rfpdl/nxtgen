<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A role the viewer is allowed to give (AccessScope::canGrant), as one choice in the "Add someone" form. */
#[TypeScript]
final class GrantOptionData extends Data
{
    public function __construct(
        public string $label,
        public RoleEnum $role,
        public ScopeTypeEnum $scope_type,
        public int $scope_id,
    ) {}
}
