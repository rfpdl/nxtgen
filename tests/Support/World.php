<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Support\LevelChart;

/*
 * The shared test World — the building blocks every feature test makes its church from. Named in the
 * glossary's words (church, satellite, guardian, child). A file's own scenario (its children, its names) is
 * built only from these; the blocks themselves are checked in tests/Feature/Support/WorldTest.php.
 */

/** @param  array<model-property<Church>, mixed>  $attributes */
function church(array $attributes = []): Church
{
    return Church::factory()->create($attributes);
}

/** @param  array<model-property<Satellite>, mixed>  $attributes */
function satelliteOf(Church $church, array $attributes = []): Satellite
{
    return Satellite::factory()->for($church)->create($attributes);
}

/**
 * A class (GLOSSARY "Schedule") at `$satellite`, in the satellite's church — active, on Saturdays 13:00–15:00
 * unless the attributes say otherwise.
 *
 * @param  array<model-property<Schedule>, mixed>  $attributes
 */
function scheduleAt(Satellite $satellite, array $attributes = []): Schedule
{
    return Schedule::factory()->create(['church_id' => $satellite->church_id, 'satellite_id' => $satellite->id] + $attributes);
}

/**
 * A Session (GLOSSARY): `$schedule` on `$date` — Saturday 31 Oct 2026 unless told otherwise — at its satellite.
 */
function sessionOf(Schedule $schedule, string $date = '2026-10-31'): Session
{
    return Session::factory()->create(['schedule_id' => $schedule->id, 'satellite_id' => $schedule->satellite_id, 'church_id' => $schedule->church_id, 'date' => $date]);
}

/**
 * `$child` checked in to `$session`, in the session's satellite's Squad for the child's Level and gender (made from the
 * chart if the satellite has none) — undone when the attributes say so. The child needs a Level.
 *
 * @param  array<model-property<CheckIn>, mixed>  $attributes
 */
function checkedIn(Session $session, Child $child, array $attributes = []): CheckIn
{
    $level = $child->level ?? throw new \LogicException('A child is checked in at a Level: give the child one.');
    $squad = Squad::query()->firstOrCreate([
        'church_id' => $session->church_id, 'satellite_id' => $session->satellite_id,
        'level' => $level, 'gender' => LevelChart::squadGender($level, $child->gender),
    ]);

    return CheckIn::factory()->create(['church_id' => $session->church_id, 'session_id' => $session->id, 'child_id' => $child->id, 'squad_id' => $squad->id] + $attributes);
}

/**
 * A family whose home is `$satellite`, in the satellite's church.
 *
 * @param  array<model-property<Family>, mixed>  $attributes
 */
function familyAt(Satellite $satellite, array $attributes = []): Family
{
    return Family::factory()->create(['church_id' => $satellite->church_id, 'home_satellite_id' => $satellite->id] + $attributes);
}

/**
 * The family's guardian who signs in, as `$user`. A family has one login, and it is the primary guardian's
 * (the family email) — so this guardian is always made primary. Guardians without a login: otherGuardianOf().
 *
 * @param  array<model-property<Guardian>, mixed>  $attributes
 */
function guardianOf(Family $family, User $user, array $attributes = []): Guardian
{
    $guardian = Guardian::factory()->create(['family_id' => $family->id, 'church_id' => $family->church_id, 'user_id' => $user->id] + $attributes);
    $family->forceFill(['primary_guardian_id' => $guardian->id])->save();

    return $guardian;
}

/**
 * One of the family's other guardians (a father, a grandparent …): no login, never the primary.
 *
 * @param  array<model-property<Guardian>, mixed>  $attributes
 */
function otherGuardianOf(Family $family, array $attributes = []): Guardian
{
    return Guardian::factory()->create(['family_id' => $family->id, 'church_id' => $family->church_id, 'user_id' => null] + $attributes);
}

/** @param  array<model-property<Child>, mixed>  $attributes */
function childOf(Family $family, array $attributes = []): Child
{
    return Child::factory()->create(['family_id' => $family->id, 'church_id' => $family->church_id] + $attributes);
}

function atChurch(Church $church): RoleScope
{
    return new RoleScope($church, ScopeTypeEnum::Church, $church->id);
}

function atSatellite(Satellite $satellite): RoleScope
{
    return new RoleScope($satellite->church()->firstOrFail(), ScopeTypeEnum::Satellite, $satellite->id);
}

/**
 * A new user holding exactly one role, at one scope.
 *
 * @param  array<model-property<User>, mixed>  $user
 */
function staffMember(RoleEnum $role, RoleScope $scope, array $user = []): User
{
    $made = User::factory()->create($user);
    grantRole($made, $role, $scope);

    return $made;
}

/**
 * Gives an existing user a role; `active: false` is a revoked role.
 */
function grantRole(User $user, RoleEnum $role, RoleScope $scope, bool $active = true): void
{
    RoleAssignment::factory()->create([
        'church_id' => $scope->church->id, 'user_id' => $user->id, 'role' => $role,
        'scope_type' => $scope->type, 'scope_id' => $scope->id, 'is_active' => $active,
    ]);
}
