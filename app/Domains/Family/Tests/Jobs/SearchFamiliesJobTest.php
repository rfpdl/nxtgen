<?php

declare(strict_types=1);

use App\Data\Models\Church;
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

/**
 * Two Reyes families: Ana's (Amara, Ben) and Joy's (Amara "Mimi", Carla), plus a Santos family with a child Ana.
 *
 * @return array{church: Church, ana: int, joy: int, santos: int}
 */
function twoReyes(): array
{
    $church = church();
    $satellite = satelliteOf($church);
    $ana = familyAt($satellite);
    guardianOf($ana, User::factory()->create(['email' => 'ana.r@example.com']), ['first_name' => 'Ana', 'last_name' => 'Reyes', 'phone' => '0501112233']);
    childOf($ana, ['first_name' => 'Amara', 'nickname' => 'Amara', 'last_name' => 'Reyes']);
    childOf($ana, ['first_name' => 'Benjamin', 'nickname' => 'Ben', 'last_name' => 'Reyes']);
    $joy = familyAt($satellite);
    guardianOf($joy, User::factory()->create(['email' => 'joy.r@example.com']), ['first_name' => 'Joy', 'last_name' => 'Reyes', 'phone' => '0504445566']);
    childOf($joy, ['first_name' => 'Amara', 'nickname' => 'Mimi', 'last_name' => 'Reyes']);
    childOf($joy, ['first_name' => 'Carla', 'nickname' => 'Carla', 'last_name' => 'Reyes']);
    $santos = familyAt($satellite);
    guardianOf($santos, User::factory()->create(['email' => 'grace.s@example.com']), ['first_name' => 'Grace', 'last_name' => 'Santos', 'phone' => '0507778899']);
    childOf($santos, ['first_name' => 'Ana', 'nickname' => 'Ana', 'last_name' => 'Santos']);

    return ['church' => $church, 'ana' => $ana->id, 'joy' => $joy->id, 'santos' => $santos->id];
}

it('narrows with every word: each must hit the SAME family — a parent, a child, in any order', function (string $query, array $who): void {
    $w = twoReyes();

    $found = (new SearchFamiliesJob(church: $w['church'], query: $query))->handle();

    $ids = [];
    foreach ($who as $k) {
        $ids[] = match ($k) {
            'ana' => $w['ana'],
            'joy' => $w['joy'],
            default => $w['santos'],
        };
    }
    sort($ids);

    expect($found->pluck('id')->sort()->values()->all())->toBe($ids);
})->with([
    // [query, the families found: 'ana', 'joy' or 'santos']
    'one word finds both' => ['Reyes', ['ana', 'joy']],
    'parent + last name' => ['Ana Reyes', ['ana']],
    'last name + parent' => ['reyes ana', ['ana']],
    'child + last name' => ['Carla Reyes', ['joy']],
    'two children of one family' => ['Amara Ben', ['ana']],
    'a nickname + last name' => ['Mimi Reyes', ['joy']],
    'no family has both words' => ['Joy Ben', []],
    'one word on A, the other on B' => ['Grace Reyes', []],
    'extra spaces' => ['  Ana    Reyes ', ['ana']],
    'a one-letter word is skipped' => ['Ana R', ['ana', 'santos']],
    'an email + a child' => ['joy.r@example Carla', ['joy']],
]);

it('still finds a phone typed with spaces, whole, or a part of one with a name', function (string $query, string $who): void {
    $w = twoReyes();

    expect((new SearchFamiliesJob(church: $w['church'], query: $query))->handle()->pluck('id')->all())->toBe([$who === 'ana' ? $w['ana'] : $w['joy']]);
})->with([
    'spaced like a phone' => ['050 444 5566', 'joy'],
    'with a plus and dashes' => ['+050-111-2233', 'ana'],
    'a name and phone digits' => ['Reyes 5566', 'joy'],
]);

it('reads at most five words', function (): void {
    $w = twoReyes();

    // The sixth word ("Zed") is never asked about, so Ana's family still matches the first five.
    expect((new SearchFamiliesJob(church: $w['church'], query: 'Ana Reyes Amara Ben ana.r Zed'))->handle()->pluck('id')->all())->toBe([$w['ana']]);
});

it('matches a phone typed with spaces next to a name — the name narrows, the number must be a parent\'s', function (string $query, array $found): void {
    $w = twoReyes();

    $ids = (new SearchFamiliesJob(church: $w['church'], query: $query))->handle()->pluck('id')->all();

    $expected = [];
    foreach ($found as $k) {
        $expected[] = $k === 'ana' ? $w['ana'] : $w['joy'];
    }

    expect($ids)->toBe($expected);
})->with([
    'name + spaced phone' => ['Reyes 050 444 5566', ['joy']],
    'spaced phone alone' => ['050 444 5566', ['joy']],
    'name + phone in one word' => ['Reyes 0504445566', ['joy']],
    'another family\'s name + the phone' => ['Ana 050 444 5566', []],
]);

it('reads a non-breaking space as a space', function (): void {
    $w = twoReyes();

    expect((new SearchFamiliesJob(church: $w['church'], query: "Ana\u{00A0}Reyes"))->handle()->pluck('id')->all())->toBe([$w['ana']]);
});

it('never compares the digits inside an email with phones', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $roy = familyAt($satellite);
    guardianOf($roy, User::factory()->create(['email' => 'roy1985@example.com']), ['first_name' => 'Roy']);
    $phone = familyAt($satellite);
    guardianOf($phone, User::factory()->create(['email' => 'x@example.com']), ['first_name' => 'Nora', 'phone' => '0501985000']);

    expect((new SearchFamiliesJob(church: $church, query: 'roy1985@example'))->handle()->pluck('id')->all())->toBe([$roy->id]);
});

it('treats a backslash in the search as a letter', function (): void {
    $church = church();
    childOf(familyAt(satelliteOf($church)), ['first_name' => 'Amara', 'nickname' => 'Amara']);

    expect((new SearchFamiliesJob(church: $church, query: 'Am\\ara'))->handle())->toBeEmpty();
});
