<?php

declare(strict_types=1);

use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\Jobs\CreateOtherGuardianJob;
use App\Enums\RelationshipEnum;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* One of the family's other guardians: no login and no email, ever — the family's one login is the primary's. */

it('adds another guardian with no login and no email, who may pick up', function (): void {
    $family = familyAt(satelliteOf(church()));
    $details = new GuardianEditData(first_name: 'Lola', last_name: 'Santos', relationship: RelationshipEnum::Grandparent, phone: '0501112222', ministries: ['Ushers']);

    $guardian = (new CreateOtherGuardianJob(family: $family, details: $details))->handle();

    expect([$guardian->church_id, $guardian->family_id, $guardian->user_id, $guardian->email])->toBe([$family->church_id, $family->id, null, null])
        ->and([$guardian->first_name, $guardian->relationship, $guardian->phone, $guardian->ministries, $guardian->can_pick_up])
        ->toBe(['Lola', RelationshipEnum::Grandparent, '0501112222', ['Ushers'], true]);
});
