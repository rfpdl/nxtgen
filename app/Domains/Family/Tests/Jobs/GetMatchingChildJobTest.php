<?php

declare(strict_types=1);

use App\Domains\Family\Jobs\GetMatchingChildJob;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* The child in this church with the same names and birthday — so a corrected registry row is not inserted twice. */

it('matches on first name, last name and birthday, ignoring case and spacing', function (): void {
    $church = church();
    $child = childOf(familyAt(satelliteOf($church)), ['first_name' => 'Amara', 'last_name' => 'Santos', 'date_of_birth' => '2019-03-04']);

    $found = (new GetMatchingChildJob(church: $church, firstName: ' AMARA ', lastName: 'santos', dateOfBirth: '2019-03-04'))->handle();

    expect($found?->id)->toBe($child->id);
});

it('does not match a different birthday or a different name', function (string $first, string $last, string $dob): void {
    $church = church();
    childOf(familyAt(satelliteOf($church)), ['first_name' => 'Amara', 'last_name' => 'Santos', 'date_of_birth' => '2019-03-04']);

    expect((new GetMatchingChildJob(church: $church, firstName: $first, lastName: $last, dateOfBirth: $dob))->handle())->toBeNull();
})->with([
    'another birthday' => ['Amara', 'Santos', '2019-03-05'],
    'another first name' => ['Amira', 'Santos', '2019-03-04'],
    'another last name' => ['Amara', 'Cruz', '2019-03-04'],
]);

it('does not match the same child in another church', function (): void {
    childOf(familyAt(satelliteOf(church())), ['first_name' => 'Amara', 'last_name' => 'Santos', 'date_of_birth' => '2019-03-04']);

    expect((new GetMatchingChildJob(church: church(), firstName: 'Amara', lastName: 'Santos', dateOfBirth: '2019-03-04'))->handle())->toBeNull();
});
