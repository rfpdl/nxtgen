<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Data\Models\Organization;
use App\Enums\ScopeTypeEnum;

/**
 * Where a test role applies — always inside one church, so a role's church, scope type and scope id can never
 * disagree. Made by `atChurch()` / `atSatellite()`; construct it directly only for a deliberately odd scope.
 */
final readonly class RoleScope
{
    public function __construct(
        public Organization $church,
        public ScopeTypeEnum $type,
        public int $id,
    ) {}
}
