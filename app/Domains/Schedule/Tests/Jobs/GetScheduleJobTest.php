<?php

declare(strict_types=1);

use App\Domains\Schedule\Jobs\GetScheduleJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/* A class by id from the address — found only inside this church, and only while active. */

it('finds an active class in this church, with its satellite', function (): void {
    $church = church();
    $satellite = satelliteOf($church, ['name' => 'CCF DXB']);
    $class = scheduleAt($satellite, ['name' => '1PM']);

    $found = (new GetScheduleJob(church: $church, id: $class->id))->handle();

    expect([$found->name, $found->satellite->name])->toBe(['1PM', 'CCF DXB']);
});

it('does not find another church\'s class by its id', function (): void {
    $theirs = scheduleAt(satelliteOf(church()));

    (new GetScheduleJob(church: church(), id: $theirs->id))->handle();
})->throws(ModelNotFoundException::class);

it('does not find a retired class', function (): void {
    $church = church();
    $retired = scheduleAt(satelliteOf($church), ['is_active' => false]);

    (new GetScheduleJob(church: $church, id: $retired->id))->handle();
})->throws(ModelNotFoundException::class);
