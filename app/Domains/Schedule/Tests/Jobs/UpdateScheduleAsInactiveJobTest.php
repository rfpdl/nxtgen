<?php

declare(strict_types=1);

use App\Domains\Schedule\Jobs\UpdateScheduleAsInactiveJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/* Retiring a class: the row stays for the record, switched off, and its name is free again. */

it('switches the class off and keeps the row', function (): void {
    $class = scheduleAt(satelliteOf(church()), ['name' => '1PM']);

    $retired = (new UpdateScheduleAsInactiveJob(schedule: $class))->handle();

    expect($retired->is_active)->toBeFalse()
        ->and($class->fresh()?->is_active)->toBeFalse();
});

it('frees the name for a new class at the satellite', function (): void {
    $satellite = satelliteOf(church());
    $class = scheduleAt($satellite, ['name' => '1PM']);

    (new UpdateScheduleAsInactiveJob(schedule: $class))->handle();

    expect(scheduleAt($satellite, ['name' => '1PM'])->is_active)->toBeTrue();
});
