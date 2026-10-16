<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\User\Jobs\UpdateUserPasswordJob;
use Illuminate\Support\Facades\Hash;

/* Sets a backup password — stored hashed, never plain. */

it('stores the password hashed, so it checks but is not readable', function (): void {
    $user = User::factory()->create(['password' => null]);

    (new UpdateUserPasswordJob(user: $user, password: 'correct horse battery'))->handle();

    $stored = User::query()->findOrFail($user->id)->password ?? '';
    expect($stored)->not->toBe('correct horse battery')
        ->and(Hash::check('correct horse battery', $stored))->toBeTrue();
});
