<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Child;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\FamilyClaim;
use App\Data\Models\Group;
use App\Data\Models\Guardian;
use App\Data\Models\MagicLink;
use App\Data\Models\Organization;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Enums\ChildStatusEnum;
use App\Enums\FamilyClaimStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RegistrationOutcomeEnum;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RelationshipEnum;
use App\Notifications\MagicLinkNotification;
use App\Operations\Registration\RegisterFamilyOperation;
use App\Operations\Registration\RegistrationResultData;
use Illuminate\Support\Facades\Notification;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

beforeEach(function (): void {
    Notification::fake();
});

/** @return array{0: Organization, 1: Branch} */
function registrationChurch(): array
{
    $org = church(['moving_up_reference_date' => '2027-02-01']);

    return [$org, satelliteOf($org)];
}

/** @param list<ChildDetailsData>|null $children */
function registration(Organization $org, Branch $branch, string $email = 'Janice@Example.com ', ?array $children = null, RegistrationSourceEnum $source = RegistrationSourceEnum::Self, ?User $by = null): FamilyRegistrationData
{
    return new FamilyRegistrationData(
        organization: $org,
        homeBranch: $branch,
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
    [$org, $branch] = registrationChurch();
    $result = register(registration($org, $branch));

    $user = User::query()->sole();
    $family = Family::query()->sole();
    $guardian = Guardian::query()->sole();

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::Registered)
        ->and($user->email)->toBe('janice@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and($guardian->user_id)->toBe($user->id)
        ->and($family->primary_guardian_id)->toBe($guardian->id)
        ->and($family->home_branch_id)->toBe($branch->id)
        ->and($family->children()->count())->toBe(1)
        ->and(Child::query()->sole()->status)->toBe(ChildStatusEnum::Active)
        ->and(MagicLink::query()->sole()->purpose)->toBe(MagicLinkPurposeEnum::VerifyEmail);

    Notification::assertSentOnDemand(MagicLinkNotification::class);
});

it('does not register a second family for an email already registered in this church', function (): void {
    [$org, $branch] = registrationChurch();
    register(registration($org, $branch));

    $result = register(registration($org, $branch, email: 'janice@example.com'));

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::AlreadyRegistered)
        ->and(Family::query()->count())->toBe(1)
        ->and(MagicLink::query()->latest('id')->first()?->purpose)->toBe(MagicLinkPurposeEnum::Login);
});

it('creates a pending claim when the email belongs to a login with no family here (e.g. a Lifeshaper)', function (): void {
    [$org, $branch] = registrationChurch();
    $staff = User::factory()->create(['email' => 'janice@example.com']);

    $result = register(registration($org, $branch));

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
    [$org, $branch] = registrationChurch();
    User::factory()->create(['email' => 'janice@example.com']);
    register(registration($org, $branch));

    $result = register(registration($org, $branch));

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::ClaimResent)
        ->and(Family::query()->count())->toBe(1)
        ->and(FamilyClaim::query()->count())->toBe(1)
        ->and(MagicLink::query()->count())->toBe(2);
});

it('lets the same parent register separately in another church', function (): void {
    [$org, $branch] = registrationChurch();
    register(registration($org, $branch));
    $otherOrg = church();

    $result = register(registration($otherOrg, satelliteOf($otherOrg)));

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::ClaimSent)
        ->and(Family::query()->count())->toBe(2);
});

it('suggests a group from age on the reference date and gender', function (): void {
    [$org, $branch] = registrationChurch();
    Group::factory()->for($org)->create(['name' => 'Level 2 Girls', 'min_age' => 6, 'max_age' => 7, 'gender' => GenderEnum::Girl]);
    $boys = Group::factory()->for($org)->create(['name' => 'Level 2 Boys', 'min_age' => 6, 'max_age' => 7, 'gender' => GenderEnum::Boy]);

    register(registration($org, $branch, children: [new ChildDetailsData(
        firstName: 'Aaron', middleName: null, lastName: 'Ramos', nickname: 'Aaron',
        gender: GenderEnum::Boy, dateOfBirth: '2020-01-31', allergies: null,
    )]));

    expect(Child::query()->sole()->group_id)->toBe($boys->id);
});

it('leaves the group empty when no group fits', function (): void {
    [$org, $branch] = registrationChurch();
    Group::factory()->for($org)->create(['min_age' => 10, 'max_age' => 12, 'gender' => null]);

    register(registration($org, $branch));

    expect(Child::query()->sole()->group_id)->toBeNull();
});

it('flags a possible duplicate child for review without blocking registration', function (): void {
    [$org, $branch] = registrationChurch();
    register(registration($org, $branch));

    $result = register(registration($org, $branch, email: 'dad@example.com'));

    expect($result->outcome)->toBe(RegistrationOutcomeEnum::Registered)
        ->and(Family::query()->count())->toBe(2)
        ->and(DuplicateCandidate::query()->where('subject_type', 'child')->where('reason', 'same_name_dob')->count())->toBe(1)
        ->and(DuplicateCandidate::query()->where('subject_type', 'family')->where('reason', 'same_phone')->count())->toBe(1);
});

it('records who registered on the parent\'s behalf', function (): void {
    [$org, $branch] = registrationChurch();
    $volunteer = User::factory()->create();

    register(registration($org, $branch, source: RegistrationSourceEnum::Staff, by: $volunteer));

    $family = Family::query()->sole();

    expect($family->registration_source)->toBe(RegistrationSourceEnum::Staff)
        ->and($family->registered_by_user_id)->toBe($volunteer->id);
});

it('tells a returning parent in the email why they got a sign-in link (review B3)', function (): void {
    [$org, $branch] = registrationChurch();
    register(registration($org, $branch));

    register(registration($org, $branch, email: 'janice@example.com'));

    Notification::assertSentOnDemand(MagicLinkNotification::class, fn (MagicLinkNotification $n): bool => $n->alreadyRegistered);
});

it('puts the already-registered explanation in the email itself', function (): void {
    $mail = (new MagicLinkNotification(url: 'https://x', purpose: MagicLinkPurposeEnum::Login, churchName: 'CCF', alreadyRegistered: true))->toMail(new stdClass);

    $lines = array_map(fn (mixed $l): string => is_scalar($l) ? (string) $l : '', $mail->introLines);

    expect(implode(' ', $lines))->toContain('your family is already registered');
});
