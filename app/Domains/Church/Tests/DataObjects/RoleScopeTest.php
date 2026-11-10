<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Domains\Church\DataObjects\RoleScope;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;

/*
 * Where a Role is held — one Satellite or one class; there is no church-wide Role (spec item 9) — in the one shape
 * everything that gives, removes or names a role uses.
 */

it('is one class, carrying the class\'s satellite, when the place is a class', function (): void {
    $satellite = satelliteOf(church());
    $class = scheduleAt($satellite);

    expect(RoleScope::of($class))->toEqual(new RoleScope(ScopeTypeEnum::Schedule, $class->id, $satellite->id));
});

it('is one Session, carrying its satellite, when the place is a helper\'s Session (spec item 18)', function (): void {
    $satellite = satelliteOf(church());
    $session = sessionOf(scheduleAt($satellite));

    expect(RoleScope::of($session))->toEqual(new RoleScope(ScopeTypeEnum::Session, $session->id, $satellite->id));
});

it('is one satellite when the place is a satellite', function (): void {
    $satellite = satelliteOf(church());

    expect(RoleScope::of($satellite))->toEqual(new RoleScope(ScopeTypeEnum::Satellite, $satellite->id));
});

it('reads a held role\'s place', function (): void {
    $satellite = satelliteOf(church());
    $user = staffMember(RoleEnum::Lifeshaper, atSatellite($satellite));

    $scope = RoleScope::ofAssignment(RoleAssignment::query()->where('user_id', $user->id)->firstOrFail());

    expect($scope)->toEqual(new RoleScope(ScopeTypeEnum::Satellite, $satellite->id));
});
