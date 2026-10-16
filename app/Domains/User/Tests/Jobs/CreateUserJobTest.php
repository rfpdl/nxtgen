<?php

declare(strict_types=1);

use App\Domains\User\Jobs\CreateUserJob;

/* A new login: trimmed name, normalised email, no password and not yet confirmed. */

it('creates a login with a normalised email, no password and an unconfirmed email', function (): void {
    $user = (new CreateUserJob(name: '  Janice Cruz ', email: ' Janice@Example.COM '))->handle()->fresh();

    expect([$user?->name, $user?->email, $user?->password, $user?->email_verified_at])
        ->toBe(['Janice Cruz', 'janice@example.com', null, null]);
});
