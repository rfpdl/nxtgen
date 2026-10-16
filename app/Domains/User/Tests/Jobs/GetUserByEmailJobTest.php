<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\User\Jobs\GetUserByEmailJob;

/* The login for an email, however it was typed. */

it('finds the login by its normalised email, and nothing for another address', function (): void {
    $user = User::factory()->create(['email' => 'janice@example.com']);
    User::factory()->create(['email' => 'someone@example.com']);

    expect((new GetUserByEmailJob(email: '  JANICE@example.com '))->handle()?->id)->toBe($user->id)
        ->and((new GetUserByEmailJob(email: 'nobody@example.com'))->handle())->toBeNull();
});
