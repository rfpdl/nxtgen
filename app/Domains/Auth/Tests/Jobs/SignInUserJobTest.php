<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Auth\Jobs\SignInUserJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/* Signs a login in, remembered on the device, with a fresh session id and the time it last signed in. */

it('signs the user in, remembers the device, regenerates the session and stamps last_login_at', function (): void {
    Carbon::setTestNow('2026-10-17 09:30:00');
    $user = User::factory()->create(['last_login_at' => null]);
    session()->start();
    $before = session()->getId();

    (new SignInUserJob(user: $user))->handle();

    expect(Auth::id())->toBe($user->id)
        ->and($user->fresh()?->getRememberToken())->not->toBeEmpty()
        ->and(session()->getId())->not->toBe($before)
        ->and($user->fresh()?->last_login_at?->toDateTimeString())->toBe('2026-10-17 09:30:00');
});
