<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\User\Jobs\UpdateUserPlatformAdminJob;

/* The platform admin is set, and taken back, on the login itself (spec item 12). */

it('makes a login the platform admin, and takes it back', function (): void {
    $user = User::factory()->create();

    (new UpdateUserPlatformAdminJob(user: $user, isPlatformAdmin: true))->handle();
    expect($user->fresh()?->is_platform_admin)->toBeTrue();

    (new UpdateUserPlatformAdminJob(user: $user, isPlatformAdmin: false))->handle();
    expect($user->fresh()?->is_platform_admin)->toBeFalse();
});
