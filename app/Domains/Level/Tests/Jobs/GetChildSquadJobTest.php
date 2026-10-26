<?php

declare(strict_types=1);

use App\Data\Models\Satellite;
use App\Domains\Level\Jobs\GetChildSquadJob;
use App\Enums\GenderEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * The child → Squad lookup (deferred from refactor 05 to check-in, DECISIONS 2026-10-17): THIS satellite's Squad for
 * the child's Level and gender — Level 1 is mixed, Level 2 up split into boys and girls. A visiting child joins the
 * Squad of the satellite they are checked in at, not their home satellite's.
 */

it('finds this satellite\'s Squad for the child\'s Level and gender', function (int $level, string $gender, string $squad): void {
    $church = church();
    $dxb = Satellite::factory()->for($church)->withSquads()->create();
    $child = childOf(familyAt($dxb), ['level' => $level, 'gender' => $gender]);

    $found = (new GetChildSquadJob(satellite: $dxb, child: $child))->handle();

    expect([$found?->satellite_id, $found?->name()])->toBe([$dxb->id, $squad]);
})->with([
    'Level 1 is mixed (a boy)' => [1, 'boy', 'Level 1'],
    'Level 1 is mixed (a girl)' => [1, 'girl', 'Level 1'],
    'Level 2 girls' => [2, 'girl', 'Level 2 Girls'],
    'Level 4 boys' => [4, 'boy', 'Level 4 Boys'],
]);

it('puts a visiting child in the Squad of the satellite they are at, not their home one', function (): void {
    $church = church();
    $house = Satellite::factory()->for($church)->withSquads()->create();
    $dxb = Satellite::factory()->for($church)->withSquads()->create();
    $visitor = childOf(familyAt($house), ['level' => 2, 'gender' => GenderEnum::Boy]);

    expect((new GetChildSquadJob(satellite: $dxb, child: $visitor))->handle()?->satellite_id)->toBe($dxb->id);
});

it('finds none when the satellite does not have that Squad — CreateChildSquadJob makes it', function (): void {
    $satellite = satelliteOf(church());
    $child = childOf(familyAt($satellite), ['level' => 3, 'gender' => GenderEnum::Girl]);

    expect((new GetChildSquadJob(satellite: $satellite, child: $child))->handle())->toBeNull();
});

it('refuses a child with no Level — the door picks one first', function (): void {
    $satellite = satelliteOf(church());
    $child = childOf(familyAt($satellite), ['level' => null]);

    expect(fn () => (new GetChildSquadJob(satellite: $satellite, child: $child))->handle())->toThrow(LogicException::class);
});
