<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\User\Jobs\GetUserByCredentialsJob;

/* The login whose email and backup password both match — never one without a password. */

it('returns the login when the email (in any case) and password match', function (): void {
    $user = User::factory()->withPassword('correct horse')->create(['email' => 'staff@example.com']);
    User::factory()->withPassword('correct horse')->create();

    expect((new GetUserByCredentialsJob(email: ' Staff@Example.com', password: 'correct horse'))->handle()?->id)->toBe($user->id);
});

it('returns null for a wrong password or an unknown email', function (): void {
    User::factory()->withPassword('correct horse')->create(['email' => 'staff@example.com']);

    expect((new GetUserByCredentialsJob(email: 'staff@example.com', password: 'wrong'))->handle())->toBeNull()
        ->and((new GetUserByCredentialsJob(email: 'nobody@example.com', password: 'correct horse'))->handle())->toBeNull();
});

it('never signs in a login that has no password', function (): void {
    User::factory()->create(['email' => 'parent@example.com', 'password' => null]);

    expect((new GetUserByCredentialsJob(email: 'parent@example.com', password: 'no-such-login'))->handle())->toBeNull();
});
