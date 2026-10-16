<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Domains\Church\DataObjects\RoleScope;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/* Where a Role is held — the whole Church or one Satellite — in the one shape everything that gives, removes or names a role uses. */

it('is the whole church when the place is the church', function (): void {
    $church = church();

    $scope = RoleScope::of($church);

    expect([$scope->type, $scope->id, RoleScope::churchIdOf($church)])->toBe([ScopeTypeEnum::Church, $church->id, $church->id]);
});

it('is one satellite, in that satellite\'s church, when the place is a satellite', function (): void {
    $church = church();
    $satellite = satelliteOf($church);

    $scope = RoleScope::of($satellite);

    expect([$scope->type, $scope->id, RoleScope::churchIdOf($satellite)])->toBe([ScopeTypeEnum::Satellite, $satellite->id, $church->id]);
});

it('reads a held role\'s place', function (): void {
    $satellite = satelliteOf(church());
    $user = staffMember(RoleEnum::Lifeshaper, atSatellite($satellite));

    $scope = RoleScope::ofAssignment(RoleAssignment::query()->where('user_id', $user->id)->firstOrFail());

    expect($scope)->toEqual(new RoleScope(ScopeTypeEnum::Satellite, $satellite->id));
});
