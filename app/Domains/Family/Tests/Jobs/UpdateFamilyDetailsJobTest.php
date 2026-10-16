<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\Jobs\UpdateFamilyDetailsJob;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* A family's details — home satellite, area, DGroup, notes — trimmed, blanks as null; contact email and claims untouched. */

it('saves the details, trimmed and with blanks as null', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church), ['notes' => 'old note']);
    $moved = satelliteOf($church);

    (new UpdateFamilyDetailsJob(family: $family, details: new FamilyDetailsData(
        home_satellite_id: $moved->id, area_text: ' Deira ', fb_account: '  ', wants_fb_group: true, in_dgroup: true,
        dgroup_area_text: 'Barsha', dgroup_leader_name: ' Jo Cruz ', dgroup_leader_phone: '0501234567', wants_dgroup: false,
        lifestage: 'Couple', notes: '',
    )))->handle();

    $family->refresh();
    expect($family->home_satellite_id)->toBe($moved->id)
        ->and([$family->area_text, $family->fb_account, $family->dgroup_area_text, $family->dgroup_leader_name, $family->dgroup_leader_phone, $family->lifestage, $family->notes])
        ->toBe(['Deira', null, 'Barsha', 'Jo Cruz', '0501234567', 'Couple', null])
        ->and([$family->wants_fb_group, $family->in_dgroup, $family->wants_dgroup])->toBe([true, true, false]);
});

it('does not touch the family\'s contact, primary guardian or consent', function (): void {
    $family = familyAt(satelliteOf(church()));
    $guardian = guardianOf($family, User::factory()->create());
    $family->refresh();
    $before = $family->only(['church_id', 'primary_guardian_id', 'registration_source', 'privacy_consented_at']);

    (new UpdateFamilyDetailsJob(family: $family, details: FamilyDetailsData::fromModel($family)))->handle();

    expect($family->refresh()->only(array_keys($before)))->toEqual($before)
        ->and($family->primary_guardian_id)->toBe($guardian->id);
});
