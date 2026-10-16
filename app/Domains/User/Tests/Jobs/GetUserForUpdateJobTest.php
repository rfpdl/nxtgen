<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\User\Jobs\GetUserForUpdateJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/* Re-reads a login from the database (locked for the caller's transaction), not the copy in memory. */

it('returns the row as stored now, not the stale copy it was given', function (): void {
    $user = User::factory()->create(['email' => 'old@example.com']);
    User::query()->whereKey($user->id)->update(['email' => 'new@example.com']);

    $locked = DB::transaction(fn (): User => (new GetUserForUpdateJob(user: $user))->handle());

    expect($locked->id)->toBe($user->id)
        ->and($locked->email)->toBe('new@example.com')
        ->and($user->email)->toBe('old@example.com');
});

it('fails when the login no longer exists', function (): void {
    $user = User::factory()->create();
    User::query()->whereKey($user->id)->delete();

    (new GetUserForUpdateJob(user: $user))->handle();
})->throws(ModelNotFoundException::class);
