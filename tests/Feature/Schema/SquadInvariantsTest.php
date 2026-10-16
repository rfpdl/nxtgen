<?php

declare(strict_types=1);

use App\Data\Models\Satellite;
use App\Data\Models\Squad;
use App\Enums\GenderEnum;
use Illuminate\Database\QueryException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/*
 * One Squad per Satellite, Level and gender — held by the database. A plain unique key cannot hold it for the
 * mixed Squad (MySQL treats NULLs as distinct), so the key is on a generated `gender_key` (gender, or "mixed").
 */

it('refuses a second mixed squad for the same satellite and Level', function (): void {
    $satellite = satelliteOf(church());
    Squad::factory()->for($satellite)->create(['level' => 1, 'gender' => null]);

    expect(fn () => Squad::factory()->for($satellite)->create(['level' => 1, 'gender' => null]))
        ->toThrow(QueryException::class, 'squads_satellite_id_level_gender_key_unique');
});

it('refuses a second boys\' squad for the same satellite and Level', function (): void {
    $satellite = satelliteOf(church());
    Squad::factory()->for($satellite)->create(['level' => 2, 'gender' => GenderEnum::Boy]);

    expect(fn () => Squad::factory()->for($satellite)->create(['level' => 2, 'gender' => GenderEnum::Boy]))
        ->toThrow(QueryException::class, 'squads_satellite_id_level_gender_key_unique');
});

it('allows the same Level\'s boys, girls and mixed squads, and the same squad at another satellite', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    Squad::factory()->for($satellite)->create(['level' => 2, 'gender' => GenderEnum::Boy]);
    Squad::factory()->for($satellite)->create(['level' => 2, 'gender' => GenderEnum::Girl]);
    Squad::factory()->for($satellite)->create(['level' => 2, 'gender' => null]);
    Squad::factory()->for(satelliteOf($church))->create(['level' => 2, 'gender' => GenderEnum::Boy]);

    expect(Squad::query()->count())->toBe(4);
});

it('names a squad from its Level and gender, never from a stored copy', function (?GenderEnum $gender, string $name): void {
    $squad = Squad::factory()->for(satelliteOf(church()))->create(['level' => 3, 'gender' => $gender]);

    expect($squad->fresh()?->name())->toBe($name);
})->with([
    'mixed' => [null, 'Level 3'],
    'boys' => [GenderEnum::Boy, 'Level 3 Boys'],
    'girls' => [GenderEnum::Girl, 'Level 3 Girls'],
]);

it('gives a test satellite the chart\'s squads through the same path ChurchSeeder uses', function (): void {
    $satellite = Satellite::factory()->withSquads()->create();

    expect(Squad::query()->where('satellite_id', $satellite->id)->get()->map(fn (Squad $s): string => $s->name())->sort()->values()->all())
        ->toBe(['Level 1', 'Level 2 Boys', 'Level 2 Girls', 'Level 3 Boys', 'Level 3 Girls', 'Level 4 Boys', 'Level 4 Girls'])
        ->and(Squad::query()->where('satellite_id', $satellite->id)->pluck('church_id')->unique()->all())->toBe([$satellite->church_id]);
});
