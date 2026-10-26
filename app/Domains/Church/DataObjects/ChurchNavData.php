<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The staff menu's switches (ChurchShell), shared as `church_nav` on every staff page from the request's AccessScope.
 * Navigation only — every Feature still asks the scope itself.
 */
#[TypeScript]
final class ChurchNavData extends Data
{
    public function __construct(
        public bool $can_manage_people,
        public bool $can_manage_classes,
        public bool $can_see_attendance,
    ) {}

    public static function fromScope(AccessScope $scope): self
    {
        return new self(
            can_manage_people: $scope->canManagePeople(),
            can_manage_classes: $scope->canManageClasses(),
            can_see_attendance: $scope->canSeeAttendance(),
        );
    }
}
