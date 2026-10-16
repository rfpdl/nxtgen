<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One person who serves in the church, with the roles the viewer may see. */
#[TypeScript]
final class StaffMemberData extends Data
{
    /** @param list<StaffRoleData> $roles */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public array $roles,
    ) {}
}
