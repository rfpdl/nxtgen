<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\MagicLink;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\actingAs;

/** @return array{org: Organization, htc: Branch, house: Branch, family: Family, parent: User} */
function correctionChurch(bool $verified = false): array
{
    $org = Organization::factory()->create(['slug' => 'ccf']);
    $htc = Branch::factory()->for($org)->create();
    $house = Branch::factory()->for($org)->create();
    $family = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => $htc->id]);
    $parent = User::factory()->create(['email' => 'janice@typo.com', 'email_verified_at' => $verified ? now() : null]);
    $guardian = Guardian::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'user_id' => $parent->id]);
    $family->forceFill(['primary_guardian_id' => $guardian->id])->save();

    return ['org' => $org, 'htc' => $htc, 'house' => $house, 'family' => $family, 'parent' => $parent];
}

function roleIn(Organization $org, RoleEnum $role, ScopeTypeEnum $type, int $id): User
{
    $u = User::factory()->create();
    RoleAssignment::factory()->create(['organization_id' => $org->id, 'user_id' => $u->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $id]);

    return $u;
}

describe('child status', function (): void {
    it('lets a satellite admin mark a child of their satellite as graduated', function (): void {
        ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();
        $child = Child::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id]);
        $admin = roleIn($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id);

        actingAs($admin)->put("/ccf/children/{$child->id}/status", ['status' => 'graduated'])->assertRedirect();

        $child->refresh();
        expect($child->status)->toBe(ChildStatusEnum::Graduated)->and($child->status_changed_by)->toBe($admin->id);
    });

    it('refuses a Lifeshaper', function (): void {
        ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();
        $child = Child::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id]);

        actingAs(roleIn($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))
            ->put("/ccf/children/{$child->id}/status", ['status' => 'inactive'])->assertNotFound();

        expect($child->fresh()?->status)->toBe(ChildStatusEnum::Active);
    });

    it('refuses an admin of another satellite', function (): void {
        ['org' => $org, 'house' => $house, 'family' => $family] = correctionChurch();
        $child = Child::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id]);

        actingAs(roleIn($org, RoleEnum::Admin, ScopeTypeEnum::Branch, $house->id))
            ->put("/ccf/children/{$child->id}/status", ['status' => 'inactive'])->assertNotFound();
    });
});

describe('correcting the family email (v1 rule)', function (): void {
    it('fixes a mistyped, unconfirmed parent email and sends a new confirmation link', function (): void {
        Notification::fake();
        ['org' => $org, 'family' => $family, 'parent' => $parent] = correctionChurch();
        $version = $parent->credential_version;

        actingAs(roleIn($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
            ->put("/ccf/families/{$family->id}/email", ['email' => 'janice@example.com'])
            ->assertRedirect()->assertSessionHas('success');

        $parent->refresh();
        expect($parent->email)->toBe('janice@example.com')
            ->and($parent->credential_version)->toBe($version + 1)
            ->and(MagicLink::query()->latest('id')->first()?->purpose)->toBe(MagicLinkPurposeEnum::VerifyEmail);
    });

    it('refuses when the login is also a staff account, and changes nothing', function (): void {
        ['org' => $org, 'family' => $family, 'parent' => $parent] = correctionChurch();
        RoleAssignment::factory()->create(['organization_id' => $org->id, 'user_id' => $parent->id]);

        actingAs(roleIn($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
            ->put("/ccf/families/{$family->id}/email", ['email' => 'janice@example.com'])
            ->assertRedirect()->assertSessionHas('error');

        expect($parent->fresh()?->email)->toBe('janice@typo.com');
    });

    it('refuses when the email is already confirmed', function (): void {
        ['org' => $org, 'family' => $family, 'parent' => $parent] = correctionChurch(verified: true);

        actingAs(roleIn($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
            ->put("/ccf/families/{$family->id}/email", ['email' => 'janice@example.com'])
            ->assertSessionHas('error');

        expect($parent->fresh()?->email)->toBe('janice@typo.com');
    });

    it('refuses an email someone else already uses', function (): void {
        ['org' => $org, 'family' => $family] = correctionChurch();
        User::factory()->create(['email' => 'taken@example.com']);

        actingAs(roleIn($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
            ->put("/ccf/families/{$family->id}/email", ['email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');
    });

    it('refuses a Lifeshaper', function (): void {
        ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();

        actingAs(roleIn($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))
            ->put("/ccf/families/{$family->id}/email", ['email' => 'janice@example.com'])
            ->assertNotFound();
    });
});

it('says honestly when the corrected email could not be sent because of the per-address limit (review-spar r2)', function (): void {
    Notification::fake();
    ['org' => $org, 'family' => $family, 'parent' => $parent] = correctionChurch();
    RateLimiter::hit('magic-link-mail:'.hash('sha256', 'janice@example.com'), 600);
    foreach (range(1, 4) as $_) {
        RateLimiter::hit('magic-link-mail:'.hash('sha256', 'janice@example.com'), 600);
    }

    actingAs(roleIn($org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))
        ->put("/ccf/families/{$family->id}/email", ['email' => 'janice@example.com'])
        ->assertSessionHas('error', 'Email updated, but too many emails went to this address recently, so no link was sent. Use "Send the confirmation link again" in 10 minutes.');

    expect($parent->fresh()?->email)->toBe('janice@example.com');
    Notification::assertNothingSent();
});
