<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Group;
use App\Data\Models\Guardian;
use App\Data\Models\MagicLink;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Enums\ChildStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use OpenSpout\Reader\XLSX\Reader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/** @return array{org: Organization, htc: Branch, house: Branch} */
function adminChurch(): array
{
    $org = Organization::factory()->create(['slug' => 'ccf']);

    return ['org' => $org, 'htc' => Branch::factory()->for($org)->create(['name' => 'Holy Trinity Church']), 'house' => Branch::factory()->for($org)->create(['name' => 'House Church'])];
}

function staff(Organization $org, RoleEnum $role, ScopeTypeEnum $type, int $scopeId): User
{
    $user = User::factory()->create();
    RoleAssignment::factory()->create(['organization_id' => $org->id, 'user_id' => $user->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $scopeId]);

    return $user;
}

function registeredFamily(Organization $org, Branch $branch, string $parent, string $email, bool $verified = true): Family
{
    $family = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => $branch->id]);
    $user = User::factory()->create(['email' => $email, 'email_verified_at' => $verified ? now() : null]);
    $guardian = Guardian::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'user_id' => $user->id, 'first_name' => $parent, 'last_name' => 'Ramos', 'phone' => '0544897346']);
    $family->forceFill(['primary_guardian_id' => $guardian->id])->save();
    Child::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'nickname' => $parent.'-kid']);

    return $family;
}

describe('who gets in', function (): void {
    it('sends a guest to sign in', function (): void {
        adminChurch();
        get('/ccf/families')->assertRedirect('/login');
    });

    it('404s for a parent with no staff role', function (): void {
        ['org' => $org, 'htc' => $htc] = adminChurch();
        $family = registeredFamily($org, $htc, 'Janice', 'janice@example.com');

        actingAs($family->primaryGuardian->user ?? User::factory()->create())->get('/ccf/families')->assertNotFound();
    });

    it('404s for staff of another church', function (): void {
        adminChurch();
        $other = Organization::factory()->create();

        actingAs(staff($other, RoleEnum::Admin, ScopeTypeEnum::Organization, $other->id))->get('/ccf/families')->assertNotFound();
    });
});

describe('the family list', function (): void {
    it('shows a church admin every family', function (): void {
        ['org' => $org, 'htc' => $htc, 'house' => $house] = adminChurch();
        registeredFamily($org, $htc, 'Janice', 'janice@example.com');
        registeredFamily($org, $house, 'Mira', 'mira@example.com');

        actingAs(staff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->get('/ccf/families')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Org/Families/Index')->has('families', 2));
    });

    it('shows a Lifeshaper only their satellite, with emails masked', function (): void {
        ['org' => $org, 'htc' => $htc, 'house' => $house] = adminChurch();
        registeredFamily($org, $htc, 'Janice', 'janice@example.com');
        registeredFamily($org, $house, 'Mira', 'mira@example.com');

        actingAs(staff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))->get('/ccf/families')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->has('families', 1)
            ->where('families.0.primaryName', 'Janice Ramos')
            ->where('families.0.email', 'ja•••@example.com'));
    });

    it('filters to families whose email is not confirmed yet', function (): void {
        ['org' => $org, 'htc' => $htc] = adminChurch();
        registeredFamily($org, $htc, 'Janice', 'janice@example.com', verified: false);
        registeredFamily($org, $htc, 'Mira', 'mira@example.com');

        actingAs(staff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->get('/ccf/families?unconfirmed=1')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('families', 1)->where('families.0.emailConfirmed', false));
    });
});

describe('registering for a parent', function (): void {
    it('searches the whole church first, so a family is not registered twice', function (): void {
        ['org' => $org, 'htc' => $htc, 'house' => $house] = adminChurch();
        registeredFamily($org, $house, 'Janice', 'janice@example.com');

        actingAs(staff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))->get('/ccf/families/new?q=ramos')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Org/Families/Register')
            ->has('results', 1)
            ->where('results.0.satellite', 'House Church')
            ->where('results.0.maskedEmail', 'ja•••@example.com')
            ->missing('results.0.phone'));
    });

    it('also finds a family by a child\'s nickname or the parent\'s phone', function (): void {
        ['org' => $org, 'htc' => $htc] = adminChurch();
        registeredFamily($org, $htc, 'Janice', 'janice@example.com');
        $lifeshaper = staff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);

        actingAs($lifeshaper)->get('/ccf/families/new?q=Janice-kid')->assertInertia(fn (AssertableInertia $p) => $p->has('results', 1));
        actingAs($lifeshaper)->get('/ccf/families/new?q=897346')->assertInertia(fn (AssertableInertia $p) => $p->has('results', 1));
    });

    it('registers a new family with an unconfirmed email and records who did it', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc] = adminChurch();
        $volunteer = staff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);

        actingAs($volunteer)->post('/ccf/families', [
            'home_branch_id' => $htc->id, 'area_text' => 'Deira', 'privacy_consented' => true,
            'guardian' => ['first_name' => 'Mira', 'last_name' => 'Bonza', 'relationship' => 'mother', 'phone' => '0523508443', 'email' => 'mira@example.com'],
            'children' => [['first_name' => 'Clarion', 'middle_name' => null, 'last_name' => 'Bonza', 'nickname' => 'Clarion', 'gender' => 'boy', 'date_of_birth' => '2018-02-03', 'has_allergies' => false, 'allergies' => null]],
        ])->assertRedirect()->assertSessionHas('success');

        $family = Family::query()->sole();

        expect($family->registration_source)->toBe(RegistrationSourceEnum::Staff)
            ->and($family->registered_by_user_id)->toBe($volunteer->id)
            ->and(User::query()->where('email', 'mira@example.com')->sole()->email_verified_at)->toBeNull();
    });

    it('lets any volunteer add a child to a family found in another satellite', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc, 'house' => $house] = adminChurch();
        $family = registeredFamily($org, $house, 'Janice', 'janice@example.com');

        actingAs(staff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))->post("/ccf/families/{$family->id}/children", [
            'first_name' => 'Baby', 'middle_name' => null, 'last_name' => 'Ramos', 'nickname' => 'Baby', 'gender' => 'girl', 'date_of_birth' => '2021-01-01', 'has_allergies' => false, 'allergies' => null,
        ])->assertRedirect()->assertSessionHas('success');

        expect($family->children()->count())->toBe(2);
    });

    it('refuses to add a child to a family of another church', function (): void {
        ['org' => $org, 'htc' => $htc] = adminChurch();
        $foreign = Family::factory()->create();

        actingAs(staff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->post("/ccf/families/{$foreign->id}/children", [
            'first_name' => 'X', 'last_name' => 'Y', 'nickname' => 'X', 'gender' => 'boy', 'date_of_birth' => '2018-01-01', 'has_allergies' => false,
        ])->assertNotFound();
    });
});

describe('a family\'s page', function (): void {
    it('opens a family in scope and 404s one out of scope', function (): void {
        ['org' => $org, 'htc' => $htc, 'house' => $house] = adminChurch();
        $mine = registeredFamily($org, $htc, 'Janice', 'janice@example.com');
        $theirs = registeredFamily($org, $house, 'Mira', 'mira@example.com');
        $lifeshaper = staff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);

        actingAs($lifeshaper)->get("/ccf/families/{$mine->id}")->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p->component('Org/Families/Show')->where('family.children.0.nickname', 'Janice-kid'));
        actingAs($lifeshaper)->get("/ccf/families/{$theirs->id}")->assertNotFound();
    });

    it('resends the confirmation link for an unconfirmed email', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc] = adminChurch();
        $family = registeredFamily($org, $htc, 'Janice', 'janice@example.com', verified: false);

        actingAs(staff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))->post("/ccf/families/{$family->id}/resend")
            ->assertRedirect()->assertSessionHas('success', 'We sent the confirmation link to ja•••@example.com again.');

        expect(MagicLink::query()->latest('id')->first()?->purpose)->toBe(MagicLinkPurposeEnum::VerifyEmail);
    });
});

it('downloads the visible families as an Excel file', function (): void {
    ['org' => $org, 'htc' => $htc] = adminChurch();
    registeredFamily($org, $htc, 'Janice', 'janice@example.com');

    $response = actingAs(staff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->get('/ccf/families/export');

    $response->assertOk();
    expect((string) $response->headers->get('content-type'))->toContain('spreadsheetml')
        ->and((string) $response->headers->get('content-disposition'))->toContain('.xlsx');
});

describe('review-spar fixes', function (): void {
    it('refuses resend for a family outside a satellite admin\'s scope, and issues nothing (review A2)', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc, 'house' => $house] = adminChurch();
        $theirs = registeredFamily($org, $house, 'Mira', 'mira@example.com', verified: false);
        $before = MagicLink::query()->count();

        actingAs(staff($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id))->post("/ccf/families/{$theirs->id}/resend")->assertNotFound();

        expect(MagicLink::query()->count())->toBe($before);
        Notification::assertNothingSent();
    });

    it('refuses resend for a class admin', function (): void {
        ['org' => $org, 'htc' => $htc] = adminChurch();
        $family = registeredFamily($org, $htc, 'Janice', 'janice@example.com', verified: false);

        actingAs(staff($org, RoleEnum::Admin, ScopeTypeEnum::EventSeries, 99))->post("/ccf/families/{$family->id}/resend")->assertNotFound();
    });

    it('says honestly when the email was NOT sent because of the per-address limit (review B3)', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc] = adminChurch();
        $family = registeredFamily($org, $htc, 'Janice', 'janice@example.com', verified: false);
        $lifeshaper = staff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);

        foreach (range(1, 5) as $_) {
            actingAs($lifeshaper)->post("/ccf/families/{$family->id}/resend");
        }

        actingAs($lifeshaper)->post("/ccf/families/{$family->id}/resend")
            ->assertSessionHas('error', 'Too many emails were sent to this address recently. Please try again in 10 minutes.');
    });

    it('keeps every family field when a volunteer registers for a parent (review B6)', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc] = adminChurch();

        actingAs(staff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))->post('/ccf/families', [
            'home_branch_id' => $htc->id, 'area_text' => 'Deira', 'privacy_consented' => true,
            'dgroup_area_text' => 'Barsha', 'dgroup_leader_phone' => '0501234567', 'lifestage' => 'Couple',
            'guardian' => ['first_name' => 'Mira', 'last_name' => 'Bonza', 'relationship' => 'mother', 'phone' => '0523508443', 'email' => 'mira2@example.com'],
            'children' => [['first_name' => 'Clarion', 'last_name' => 'Bonza', 'nickname' => 'Clarion', 'gender' => 'boy', 'date_of_birth' => '2018-02-03', 'has_allergies' => false]],
        ])->assertRedirect();

        $family = Family::query()->sole();
        expect($family->dgroup_area_text)->toBe('Barsha')
            ->and($family->dgroup_leader_phone)->toBe('0501234567')
            ->and($family->lifestage)->toBe('Couple');
    });
});

/*
 * The registration list's search and filters (plan §6: status, group, satellite, unconfirmed email, open
 * duplicate candidates). Filters narrow what the viewer may see — they never widen it.
 */
describe('searching and filtering the list', function (): void {
    it('finds families by parent, email, phone or child — the same matching as the door search', function (): void {
        ['org' => $org, 'htc' => $htc] = adminChurch();
        registeredFamily($org, $htc, 'Janice', 'janice@example.com');
        registeredFamily($org, $htc, 'Mira', 'mira@example.com');
        $admin = staff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id);

        foreach (['Janice', 'janice@exa', 'Janice-kid'] as $q) {
            actingAs($admin)->get('/ccf/families?q='.urlencode($q))->assertInertia(fn (AssertableInertia $p) => $p
                ->has('families', 1)->where('families.0.primaryName', 'Janice Ramos')->where('filters.q', $q));
        }
    });

    it('filters by satellite, child status and group, and offers those choices', function (): void {
        ['org' => $org, 'htc' => $htc, 'house' => $house] = adminChurch();
        $a = registeredFamily($org, $htc, 'Janice', 'janice@example.com');
        registeredFamily($org, $house, 'Mira', 'mira@example.com');
        $group = Group::factory()->create(['organization_id' => $org->id, 'name' => 'Level 2 Girls']);
        Child::query()->where('family_id', $a->id)->update(['status' => ChildStatusEnum::Inactive, 'group_id' => $group->id]);
        $admin = staff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id);

        actingAs($admin)->get("/ccf/families?satellite={$house->id}")->assertInertia(fn (AssertableInertia $p) => $p
            ->has('families', 1)->where('families.0.primaryName', 'Mira Ramos')
            ->has('options.satellites', 2)->has('options.groups', 1)->has('options.statuses', 3));
        actingAs($admin)->get('/ccf/families?status=inactive')->assertInertia(fn (AssertableInertia $p) => $p
            ->has('families', 1)->where('families.0.primaryName', 'Janice Ramos'));
        actingAs($admin)->get("/ccf/families?group={$group->id}")->assertInertia(fn (AssertableInertia $p) => $p
            ->has('families', 1)->where('families.0.primaryName', 'Janice Ramos'));
    });

    it('filters to families with an open possible duplicate, on either side', function (): void {
        ['org' => $org, 'htc' => $htc] = adminChurch();
        $old = registeredFamily($org, $htc, 'Janice', 'janice@example.com');
        $new = registeredFamily($org, $htc, 'Jan', 'jan@example.com');
        registeredFamily($org, $htc, 'Mira', 'mira@example.com')->guardians()->update(['phone' => '0500000099']);
        (new CreateDuplicateCandidatesJob($new))->handle();

        actingAs(staff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->get('/ccf/families?duplicates=1')
            // Newest first: the new family, then the one it may duplicate; Mira (different phone) is not listed.
            ->assertInertia(fn (AssertableInertia $p) => $p->has('families', 2)
                ->where('families.0.id', $new->id)->where('families.1.id', $old->id));
    });

    it('never widens a satellite admin\'s view by picking another satellite', function (): void {
        ['org' => $org, 'htc' => $htc, 'house' => $house] = adminChurch();
        registeredFamily($org, $house, 'Mira', 'mira@example.com');

        actingAs(staff($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id))->get("/ccf/families?satellite={$house->id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->has('families', 0)->has('options.satellites', 1));
    });

    it('downloads what the filters show', function (): void {
        ['org' => $org, 'htc' => $htc, 'house' => $house] = adminChurch();
        registeredFamily($org, $htc, 'Janice', 'janice@example.com');
        registeredFamily($org, $house, 'Mira', 'mira@example.com');

        $response = actingAs(staff($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->get("/ccf/families/export?satellite={$house->id}");

        $download = $response->baseResponse;
        if (! $download instanceof BinaryFileResponse) {
            throw new RuntimeException('Expected a file download.');
        }
        $file = $download->getFile()->getPathname();
        $rows = [];
        $reader = new Reader;
        $reader->open($file);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = implode('|', array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $row->toArray()));
            }
            break;
        }
        $reader->close();

        $sheet = implode("\n", $rows);
        expect(str_contains($sheet, 'Mira'))->toBeTrue()->and(str_contains($sheet, 'Janice'))->toBeFalse();
    });
});
