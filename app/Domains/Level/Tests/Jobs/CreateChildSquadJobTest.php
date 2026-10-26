<?php

declare(strict_types=1);

use App\Data\Models\Squad;
use App\Domains\Level\Jobs\CreateChildSquadJob;
use App\Enums\GenderEnum;

use function Tests\Support\atTheSameTime;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\withCommittedWorld;

/* A Squad the chart has and the satellite lacks is made at the door — once, even when two phones need it at once. */

it('makes this satellite\'s Squad for the child\'s Level and gender, the chart\'s way', function (int $level, string $gender, string $name): void {
    $satellite = satelliteOf(church());
    $child = childOf(familyAt($satellite), ['level' => $level, 'gender' => $gender]);

    $squad = (new CreateChildSquadJob(satellite: $satellite, child: $child))->handle();

    expect([$squad->church_id, $squad->satellite_id, $squad->name()])->toBe([$satellite->church_id, $satellite->id, $name]);
})->with([
    'Level 1 is mixed' => [1, 'boy', 'Level 1'],
    'Level 3 girls' => [3, 'girl', 'Level 3 Girls'],
]);

it('gives back the Squad another phone made at the same moment, and there is one', function (): void {
    $satellite = satelliteOf(church());
    $child = childOf(familyAt($satellite), ['level' => 2, 'gender' => GenderEnum::Boy]);

    withCommittedWorld(function () use ($satellite, $child): void {
        $ids = atTheSameTime(
            fn (): int => (new CreateChildSquadJob(satellite: $satellite, child: $child))->handle()->id,
            fn (): int => (new CreateChildSquadJob(satellite: $satellite, child: $child))->handle()->id,
        );

        expect($ids[0])->toBe($ids[1])->and(Squad::query()->count())->toBe(1);
    });
});

it('refuses a child with no Level', function (): void {
    $satellite = satelliteOf(church());

    $child = childOf(familyAt($satellite), ['level' => null]);

    expect(fn () => (new CreateChildSquadJob(satellite: $satellite, child: $child))->handle())->toThrow(LogicException::class);
});
