<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The staff menu's switches (ChurchShell), shared as `church_nav` on every staff page from the request's AccessScope,
 * and the area the page is in (RouteArea: 'admin' or 'serve') — which menu to show, and where a
 * page shown in both areas (Admin and Serve) builds its links. Navigation only — every Feature still asks the scope itself.
 */
#[TypeScript]
final class ChurchNavData extends Data
{
    public function __construct(
        public ?string $area,
        public bool $can_use_admin,
        public bool $can_use_full_admin,
        public bool $can_manage_people,
        public bool $can_manage_classes,
        public bool $can_see_attendance,
        public bool $can_manage_cards,
        public bool $can_move_up,
        public bool $can_manage_links,
    ) {}

    public static function fromScope(AccessScope $scope, ?string $area): self
    {
        return new self(
            area: $area,
            can_use_admin: $scope->canUseAdmin(),
            can_use_full_admin: $scope->canUseFullAdmin(),
            can_manage_people: $scope->canManagePeople(),
            can_manage_classes: $scope->canManageClasses(),
            can_see_attendance: $scope->canSeeAttendance(),
            can_manage_cards: $scope->canManageCards(),
            can_move_up: $scope->canMoveUp(),
            can_manage_links: $scope->canManageLinks(),
        );
    }
}
