<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Domains\Family\Jobs\CreateFamilyJob;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RelationshipEnum;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* The family row of a registration: its church, home satellite, the answers given, and who registered it. */

it('creates the family at its home satellite, trimming answers and recording consent and who registered it', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $staff = User::factory()->create();
    $registration = new FamilyRegistrationData(
        church: $church, home_satellite: $satellite,
        primary: new GuardianDetailsData(first_name: 'Janice', last_name: 'Santos', relationship: RelationshipEnum::Mother, phone: null, email: 'j@example.com'),
        children: [], area_text: ' Al Barsha ', privacy_consented: true, source: RegistrationSourceEnum::Staff,
        registered_by: $staff, fb_account: '   ', in_dgroup: true, dgroup_leader_name: ' Ate Joy ',
    );

    $family = (new CreateFamilyJob(registration: $registration))->handle();

    expect([$family->church_id, $family->home_satellite_id, $family->registered_by_user_id])->toBe([$church->id, $satellite->id, $staff->id])
        ->and([$family->area_text, $family->fb_account, $family->dgroup_leader_name])->toBe(['Al Barsha', null, 'Ate Joy'])
        ->and($family->in_dgroup)->toBeTrue()
        ->and($family->privacy_consented_at)->not->toBeNull()
        ->and($family->registration_source)->toBe(RegistrationSourceEnum::Staff);
});

it('records no consent time when privacy was not agreed, and no registrar for a self-registration', function (): void {
    $church = church();
    $registration = new FamilyRegistrationData(
        church: $church, home_satellite: satelliteOf($church),
        primary: new GuardianDetailsData(first_name: 'A', last_name: 'B', relationship: RelationshipEnum::Father, phone: null, email: 'a@example.com'),
        children: [], area_text: null, privacy_consented: false, source: RegistrationSourceEnum::Self,
    );

    $family = (new CreateFamilyJob(registration: $registration))->handle();

    expect($family->privacy_consented_at)->toBeNull()->and($family->registered_by_user_id)->toBeNull();
});
