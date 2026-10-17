<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\FamilyClaim;
use App\Data\Models\Guardian;
use App\Data\Models\MagicLink;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Domains\Family\DataObjects\RegistrationResultData;
use App\Enums\ChildStatusEnum;
use App\Enums\FamilyClaimStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RegistrationOutcomeEnum;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RelationshipEnum;
use App\Notifications\MagicLinkNotification;
use App\Operations\Registration\RegisterFamilyOperation;
use Illuminate\Support\Facades\Notification;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

beforeEach(function (): void {
    Notification::fake();
});

/** @return array{0: Church, 1: Satellite} */
function registrationChurch(): array
{
    $church = church(['moving_up_reference_date' => '2027-02-01']);

    return [$church, satelliteOf($church)];
}

/** @param list<ChildDetailsData>|null $children */
function registration(Church $church, Satellite $satellite, string $email = 'Janice@Example.com ', ?array $children = null, RegistrationSourceEnum $source = RegistrationSourceEnum::Self, ?User $by = null): FamilyRegistrationData
{
    return new FamilyRegistrationData(
        church: $church,
        homeSatellite: $satellite,
        primary: new GuardianDetailsData(
            firstName: 'Janice', lastName: 'Ramos', relationship: RelationshipEnum::Mother,
            phone: '0544897346', email: $email, ministries: ['Music'],
        ),
        children: $children ?? [new ChildDetailsData(
            firstName: 'Alexis', middleName: 'Amara', lastName: 'Ramos', nickname: 'Amara',
            gender: GenderEnum::Girl, dateOfBirth: '2019-05-10', allergies: 'Peanuts',
        )],
        areaText: 'Al Barsha',
        privacyConsented: true,
        source: $source,
        registeredBy: $by,
    );
}

function register(FamilyRegistrationData $data): RegistrationResultData
{
    return (new RegisterFamilyOperation($data))->handle();
}

it('registers a new family: unverified login, linked primary guardian, children, confirm-email link', function (): void {
    [$church, $satellite] = registrationChurch();
    $result = register(registration($church, $satellite));

    $user = User::query()->sole();
    $family = Family::query()->sole();
    $guardian = Guardian::query()->sole();

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::Registered)
        ->and($user->email)->toBe('janice@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and($guardian->user_id)->toBe($user->id)
        ->and($family->primary_guardian_id)->toBe($guardian->id)
        ->and($family->home_satellite_id)->toBe($satellite->id)
        ->and($family->children()->count())->toBe(1)
        ->and(Child::query()->sole()->status)->toBe(ChildStatusEnum::Active)
        ->and(MagicLink::query()->sole()->purpose)->toBe(MagicLinkPurposeEnum::VerifyEmail);

    Notification::assertSentOnDemand(MagicLinkNotification::class);
});

it('does not register a second family for an email already registered in this church', function (): void {
    [$church, $satellite] = registrationChurch();
    register(registration($church, $satellite));

    $result = register(registration($church, $satellite, email: 'janice@example.com'));

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::AlreadyRegistered)
        ->and(Family::query()->count())->toBe(1)
        ->and(MagicLink::query()->latest('id')->first()?->purpose)->toBe(MagicLinkPurposeEnum::Login);
});

it('creates a pending claim when the email belongs to a login with no family here (e.g. a Lifeshaper)', function (): void {
    [$church, $satellite] = registrationChurch();
    $staff = User::factory()->create(['email' => 'janice@example.com']);

    $result = register(registration($church, $satellite));

    $guardian = Guardian::query()->sole();
    $claim = FamilyClaim::query()->sole();

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::ClaimSent)
        ->and(User::query()->count())->toBe(1)
        ->and($guardian->user_id)->toBeNull()
        ->and($guardian->email)->toBe('janice@example.com')
        ->and($claim->status)->toBe(FamilyClaimStatusEnum::Pending)
        ->and($claim->guardian_id)->toBe($guardian->id)
        ->and($staff->fresh()?->credential_version)->toBe(1)
        ->and(MagicLink::query()->sole()->purpose)->toBe(MagicLinkPurposeEnum::ClaimFamily);
});

it('re-sends the pending claim instead of creating a second family', function (): void {
    [$church, $satellite] = registrationChurch();
    User::factory()->create(['email' => 'janice@example.com']);
    register(registration($church, $satellite));

    $result = register(registration($church, $satellite));

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::ClaimResent)
        ->and(Family::query()->count())->toBe(1)
        ->and(FamilyClaim::query()->count())->toBe(1)
        ->and(MagicLink::query()->count())->toBe(2);
});

it('lets the same parent register separately in another church', function (): void {
    [$church, $satellite] = registrationChurch();
    register(registration($church, $satellite));
    $otherChurch = church();

    $result = register(registration($otherChurch, satelliteOf($otherChurch)));

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::ClaimSent)
        ->and(Family::query()->count())->toBe(2);
});

it('stores the Level the child\'s age suggests on the reference date', function (): void {
    [$church, $satellite] = registrationChurch();

    register(registration($church, $satellite, children: [new ChildDetailsData(
        firstName: 'Aaron', middleName: null, lastName: 'Ramos', nickname: 'Aaron',
        gender: GenderEnum::Boy, dateOfBirth: '2020-01-31', allergies: null,
    )]));

    // Seven on 2027-02-01 → Level 2.
    expect(Child::query()->sole()->level)->toBe(2)->and(Child::query()->sole()->level_set_at)->not->toBeNull();
});

it('leaves the Level empty when no Level fits the age', function (): void {
    [$church, $satellite] = registrationChurch();

    register(registration($church, $satellite, children: [new ChildDetailsData(
        firstName: 'Baby', middleName: null, lastName: 'Ramos', nickname: 'Baby',
        gender: GenderEnum::Girl, dateOfBirth: '2025-01-01', allergies: null,
    )]));

    expect(Child::query()->sole()->level)->toBeNull()->and(Child::query()->sole()->level_set_at)->toBeNull();
});

it('flags a possible duplicate child for review without blocking registration', function (): void {
    [$church, $satellite] = registrationChurch();
    register(registration($church, $satellite));

    $result = register(registration($church, $satellite, email: 'dad@example.com'));

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::Registered)
        ->and(Family::query()->count())->toBe(2)
        ->and(DuplicateCandidate::query()->where('subject_type', 'child')->where('reason', 'same_name_dob')->count())->toBe(1)
        ->and(DuplicateCandidate::query()->where('subject_type', 'family')->where('reason', 'same_phone')->count())->toBe(1);
});

it('records who registered on the parent\'s behalf', function (): void {
    [$church, $satellite] = registrationChurch();
    $staffMember = User::factory()->create();

    register(registration($church, $satellite, source: RegistrationSourceEnum::Staff, by: $staffMember));

    $family = Family::query()->sole();

    expect($family->registration_source)->toBe(RegistrationSourceEnum::Staff)
        ->and($family->registered_by_user_id)->toBe($staffMember->id);
});

// Found in review B3.
it('tells a returning parent in the email why they got a sign-in link', function (): void {
    [$church, $satellite] = registrationChurch();
    register(registration($church, $satellite));

    register(registration($church, $satellite, email: 'janice@example.com'));

    Notification::assertSentOnDemand(MagicLinkNotification::class, fn (MagicLinkNotification $n): bool => $n->alreadyRegistered);
});

it('puts the already-registered explanation in the email itself', function (): void {
    $mail = (new MagicLinkNotification(url: 'https://x', purpose: MagicLinkPurposeEnum::Login, churchName: 'CCF', alreadyRegistered: true))->toMail(new stdClass);

    $lines = array_map(fn (mixed $l): string => is_scalar($l) ? (string) $l : '', $mail->introLines);

    expect(implode(' ', $lines))->toContain('your family is already registered');
});
