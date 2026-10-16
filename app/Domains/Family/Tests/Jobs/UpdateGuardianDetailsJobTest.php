<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\Jobs\UpdateGuardianDetailsJob;
use App\Enums\RelationshipEnum;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* A parent's own details — name, relationship, phone, ministries — never their email or their login. */

it('saves the parent\'s details and leaves the email and login alone', function (): void {
    $user = User::factory()->create();
    $guardian = guardianOf(familyAt(satelliteOf(church())), $user, ['email' => 'pending@example.com']);

    (new UpdateGuardianDetailsJob(guardian: $guardian, details: new GuardianEditData(
        first_name: 'Maria', last_name: 'Santos', relationship: RelationshipEnum::Mother, phone: '0501234567', ministries: ['Worship'],
    )))->handle();

    $guardian->refresh();
    expect([$guardian->first_name, $guardian->last_name, $guardian->relationship, $guardian->phone, $guardian->ministries])
        ->toBe(['Maria', 'Santos', RelationshipEnum::Mother, '0501234567', ['Worship']])
        ->and($guardian->email)->toBe('pending@example.com')
        ->and($guardian->user_id)->toBe($user->id);
});
