<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Domains\Church\Jobs\GetRoleAssignmentJob;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\atChurch;
use function Tests\Support\church;
use function Tests\Support\staffMember;

/* A role by id from the URL — found only inside this church. */

it('finds a role in this church', function (): void {
    $church = church();
    $user = staffMember(RoleEnum::Lifeshaper, atChurch($church));
    $role = RoleAssignment::query()->where('user_id', $user->id)->sole();

    expect((new GetRoleAssignmentJob(church: $church, id: $role->id))->handle()->id)->toBe($role->id);
});

it('does not find another church\'s role by its id', function (): void {
    $other = church();
    $user = staffMember(RoleEnum::Admin, atChurch($other));
    $theirs = RoleAssignment::query()->where('user_id', $user->id)->sole();

    (new GetRoleAssignmentJob(church: church(), id: $theirs->id))->handle();
})->throws(ModelNotFoundException::class);
