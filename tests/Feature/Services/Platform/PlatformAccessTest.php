<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\call;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\staffMember;

/*
 * Who may not, and where not (spec item 12, review of issue 09): every platform action is the platform admin's alone —
 * anyone else, signed in or not, changes nothing — and even the platform admin reaches only the platform's church.
 */

/**
 * What the platform's actions could change: the satellites, the Roles and the settings, as they stand.
 *
 * @return array{array<mixed>, array<mixed>, array<mixed>}
 */
function platformState(): array
{
    return [
        Satellite::query()->orderBy('id')->get(['name', 'slug'])->toArray(),
        RoleAssignment::query()->orderBy('id')->get(['user_id', 'role', 'is_active'])->toArray(),
        Church::query()->orderBy('id')->get(['timezone', 'card_fee_minor'])->toArray(),
    ];
}

it('lets nobody but the platform admin act there: nothing changes @story#191', function (string $who, string $method, string $address, array $payload): void {
    $dubai = satelliteOf(church(), ['slug' => 'dubai']);
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dubai));
    staffMember(RoleEnum::Admin, atSatellite($dubai));
    $parent = User::factory()->create();
    guardianOf(familyAt($dubai), $parent);
    $role = RoleAssignment::query()->where('user_id', $lifeshaper->id)->sole();
    $payload = $payload === [] ? [] : [...$payload, ...(isset($payload['scope_type']) ? ['scope_id' => $dubai->id] : [])];
    $before = platformState();

    $person = match ($who) {
        'admin' => staffMember(RoleEnum::Admin, atSatellite($dubai)),
        'Lifeshaper' => $lifeshaper,
        'parent' => $parent,
        default => null,
    };
    if ($person !== null) {
        actingAs($person);
        $before = platformState();
    }

    $response = call($method, str_replace('{role}', (string) $role->id, $address), $payload);

    $person === null ? $response->assertRedirect('/login') : $response->assertNotFound();
    expect(platformState())->toBe($before);
})->with(['stranger', 'admin', 'Lifeshaper', 'parent'])->with([
    'add a satellite' => ['POST', '/platform/satellites', ['name' => 'CCF X', 'slug' => 'x']],
    'rename a satellite' => ['PUT', '/platform/satellites/dubai', ['name' => 'CCF X', 'slug' => 'x']],
    'give a Role' => ['POST', '/platform/people', ['name' => 'X', 'email' => 'x@example.com', 'role' => 'admin', 'scope_type' => 'satellite']],
    'remove a Role' => ['DELETE', '/platform/people/roles/{role}', []],
    'save the settings' => ['PUT', '/platform/settings', ['timezone' => 'UTC', 'card_fee' => '1', 'moving_up_date' => '2027-01-01']],
]);

describe('another church', function (): void {
    it('cannot rename another church\'s satellite @story#191', function (): void {
        satelliteOf(church(), ['slug' => 'dubai']);
        satelliteOf(church(), ['name' => 'Elsewhere', 'slug' => 'elsewhere']);

        actingAs(User::factory()->create(['is_platform_admin' => true]))
            ->put('/platform/satellites/elsewhere', ['name' => 'Mine now', 'slug' => 'mine'])->assertNotFound();
        expect(Satellite::query()->where('slug', 'elsewhere')->sole()->name)->toBe('Elsewhere');
    });

    it('cannot give a Role at another church\'s satellite or class, nor remove one there @story#191', function (): void {
        satelliteOf(church(), ['slug' => 'dubai']);
        $elsewhere = satelliteOf(church(), ['slug' => 'elsewhere']);
        $class = scheduleAt($elsewhere);
        $theirs = staffMember(RoleEnum::Lifeshaper, atSatellite($elsewhere));
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $give = ['name' => 'X', 'email' => 'x@example.com', 'role' => 'admin'];

        actingAs($platform)->post('/platform/people', [...$give, 'scope_type' => ScopeTypeEnum::Satellite->value, 'scope_id' => $elsewhere->id])->assertNotFound();
        actingAs($platform)->post('/platform/people', [...$give, 'scope_type' => ScopeTypeEnum::Schedule->value, 'scope_id' => $class->id])->assertNotFound();
        actingAs($platform)->delete('/platform/people/roles/'.RoleAssignment::query()->where('user_id', $theirs->id)->sole()->id)->assertNotFound();

        expect(User::query()->where('email', 'x@example.com')->exists())->toBeFalse()
            ->and(RoleAssignment::query()->where('user_id', $theirs->id)->sole()->is_active)->toBeTrue();
    });
});

it('gives only the Admin Role at a class — never a Lifeshaper there @story#198', function (): void {
    $class = scheduleAt(satelliteOf(church(), ['slug' => 'dubai']));

    actingAs(User::factory()->create(['is_platform_admin' => true]))->post('/platform/people', [
        'name' => 'X', 'email' => 'x@example.com', 'role' => 'lifeshaper', 'scope_type' => ScopeTypeEnum::Schedule->value, 'scope_id' => $class->id,
    ])->assertNotFound();
    expect(User::query()->where('email', 'x@example.com')->exists())->toBeFalse();
});
