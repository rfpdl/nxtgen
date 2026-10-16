<?php

declare(strict_types=1);

use App\Data\Models\Family;
use App\Data\Models\User;
use App\Domains\Family\Jobs\SearchFamiliesJob;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* The door search: is this family already registered? Church-wide, by parent, email, phone or a child's name. */

it('finds a family by parent name, login email, phone digits or a child\'s nickname', function (string $query): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    guardianOf($family, User::factory()->create(['email' => 'maria.santos@example.com']), ['first_name' => 'Maria', 'phone' => '0501234567']);
    childOf($family, ['first_name' => 'Amara', 'nickname' => 'Mimi']);
    familyAt(satelliteOf($church));

    $found = (new SearchFamiliesJob(church: $church, query: $query))->handle();

    expect($found->pluck('id')->all())->toBe([$family->id]);
})->with(['maria', 'maria.santos@example', '050-123-4567', 'mimi']);

it('searches across every satellite of the church', function (): void {
    $church = church();
    $here = familyAt(satelliteOf($church));
    $there = familyAt(satelliteOf($church));
    childOf($here, ['first_name' => 'Amara']);
    childOf($there, ['first_name' => 'Amara']);

    $found = (new SearchFamiliesJob(church: $church, query: 'Amara'))->handle();

    expect($found->pluck('id')->all())->toEqualCanonicalizing([$here->id, $there->id]);
});

it('never returns another church\'s family, even on an exact match', function (): void {
    $elsewhere = familyAt(satelliteOf(church()));
    guardianOf($elsewhere, User::factory()->create(), ['first_name' => 'Maria']);
    childOf($elsewhere, ['first_name' => 'Amara']);

    expect((new SearchFamiliesJob(church: church(), query: 'Maria'))->handle())->toBeEmpty()
        ->and((new SearchFamiliesJob(church: church(), query: 'Amara'))->handle())->toBeEmpty();
});

it('returns nothing for a query under two characters', function (): void {
    $church = church();
    childOf(familyAt(satelliteOf($church)), ['first_name' => 'A']);

    expect((new SearchFamiliesJob(church: $church, query: ' a '))->handle())->toBeEmpty();
});

it('returns at most twenty families, with what the lite view shows already loaded', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    foreach (range(1, 21) as $_) {
        childOf(familyAt($satellite), ['last_name' => 'Santos']);
    }

    $found = (new SearchFamiliesJob(church: $church, query: 'Santos'))->handle();

    expect($found)->toHaveCount(20)
        ->and($found->every(fn (Family $f): bool => $f->relationLoaded('primaryGuardian') && $f->relationLoaded('homeSatellite') && $f->relationLoaded('children')))->toBeTrue();
});
