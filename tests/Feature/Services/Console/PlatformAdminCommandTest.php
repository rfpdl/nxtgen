<?php

declare(strict_types=1);

use App\Data\Models\User;
use Illuminate\Testing\PendingCommand;

use function Pest\Laravel\artisan;

/*
 * The platform admin (spec item 12) is set at the terminal only — never from a screen: `nxtgen:platform-admin {email}`
 * makes a login the platform admin, `--remove` takes it back. The login must exist (the setup's first person, or anyone
 * who was given a Role); no Role is given or taken.
 */

/** @param array<string, mixed> $arguments */
function platformAdminCommand(array $arguments): PendingCommand
{
    $pending = artisan('nxtgen:platform-admin', $arguments);
    if (! $pending instanceof PendingCommand) {
        throw new RuntimeException('Expected a pending command.');
    }

    return $pending;
}

it('makes a login the platform admin, and takes it back @story#188', function (): void {
    $roy = User::factory()->create(['email' => 'roy@example.com']);

    platformAdminCommand(['email' => 'Roy@Example.com'])
        ->expectsOutputToContain('roy@example.com is now the platform admin.')->assertSuccessful();
    expect($roy->fresh()?->is_platform_admin)->toBeTrue();

    platformAdminCommand(['email' => 'roy@example.com', '--remove' => true])
        ->expectsOutputToContain('roy@example.com is no longer the platform admin.')->assertSuccessful();
    expect($roy->fresh()?->is_platform_admin)->toBeFalse();
});

it('refuses an email no login has — nobody is made from the terminal @story#189', function (): void {
    platformAdminCommand(['email' => 'nobody@example.com'])
        ->expectsOutputToContain('No login has the email nobody@example.com.')->assertFailed();

    expect(User::query()->count())->toBe(0);
});
