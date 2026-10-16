<?php

declare(strict_types=1);

use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Domains\Family\Jobs\UpdateGuardianUserJob;
use Illuminate\Database\QueryException;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* Linking a login to a guardian: the pending contact email moves to the login, so the guardian's copy is cleared. */

it('links the login and clears the guardian\'s pending email', function (): void {
    $guardian = Guardian::factory()->create(['family_id' => familyAt(satelliteOf(church()))->id, 'email' => 'maria@example.com', 'first_name' => 'Maria']);
    $user = User::factory()->create(['email' => 'maria@example.com']);

    (new UpdateGuardianUserJob(guardian: $guardian, user: $user))->handle();

    $guardian->refresh();
    expect($guardian->user_id)->toBe($user->id)
        ->and($guardian->email)->toBeNull()
        ->and($guardian->first_name)->toBe('Maria');
});

it('refuses a second family for the same login in one church', function (): void {
    $satellite = satelliteOf(church());
    $user = User::factory()->create();
    guardianOf(familyAt($satellite), $user);
    $other = Guardian::factory()->create(['family_id' => familyAt($satellite)->id]);

    expect(fn () => (new UpdateGuardianUserJob(guardian: $other, user: $user))->handle())->toThrow(QueryException::class, 'guardians_church_id_user_id_unique');
});

it('refuses a second login on one family', function (): void {
    $family = familyAt(satelliteOf(church()));
    guardianOf($family, User::factory()->create());
    $second = Guardian::factory()->create(['family_id' => $family->id]);

    expect(fn () => (new UpdateGuardianUserJob(guardian: $second, user: User::factory()->create()))->handle())->toThrow(QueryException::class, 'guardians_login_family_key_unique');
});
