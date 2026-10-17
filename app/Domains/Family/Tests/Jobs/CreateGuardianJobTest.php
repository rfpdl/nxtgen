<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Domains\Family\Jobs\CreateGuardianJob;
use App\Enums\RelationshipEnum;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* An adult on a family: linked to a login, or — until a claim is accepted — holding the email itself. */

it('links the guardian to the login and keeps no email of its own', function (): void {
    $family = familyAt(satelliteOf(church()));
    $user = User::factory()->create();
    $details = new GuardianDetailsData(firstName: ' Janice ', lastName: ' Santos ', relationship: RelationshipEnum::Mother, phone: ' 050 123 4567 ', email: 'Janice@Example.com', ministries: ['music']);

    $guardian = (new CreateGuardianJob(family: $family, details: $details, user: $user))->handle();

    expect([$guardian->church_id, $guardian->family_id, $guardian->user_id])->toBe([$family->church_id, $family->id, $user->id])
        ->and([$guardian->first_name, $guardian->last_name, $guardian->phone])->toBe(['Janice', 'Santos', '050 123 4567'])
        ->and($guardian->email)->toBeNull()
        ->and($guardian->ministries)->toBe(['music'])
        ->and($guardian->can_pick_up)->toBeTrue();
});

it('keeps the normalised email on a guardian with no login, and a blank phone as nothing', function (): void {
    $family = familyAt(satelliteOf(church()));
    $details = new GuardianDetailsData(firstName: 'Jun', lastName: 'Santos', relationship: RelationshipEnum::Father, phone: '  ', email: ' Jun@Example.COM ');

    $guardian = (new CreateGuardianJob(family: $family, details: $details, user: null))->handle();

    expect($guardian->user_id)->toBeNull()
        ->and($guardian->email)->toBe('jun@example.com')
        ->and($guardian->phone)->toBeNull();
});
