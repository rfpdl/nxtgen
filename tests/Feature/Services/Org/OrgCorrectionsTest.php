<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\MagicLink;
use App\Data\Models\Note;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\NoteKindEnum;
use App\Enums\NoteVisibilityEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\childEdit;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\familyEdit;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/** @return array{org: Organization, htc: Branch, house: Branch, family: Family, parent: User} */
function correctionChurch(bool $verified = false): array
{
    $org = church(['slug' => 'ccf']);
    $htc = satelliteOf($org);
    $house = satelliteOf($org);
    $family = familyAt($htc);
    $parent = User::factory()->create(['email' => 'janice@typo.com', 'email_verified_at' => $verified ? now() : null]);
    $guardian = guardianOf($family, $parent);

    return ['org' => $org, 'htc' => $htc, 'house' => $house, 'family' => $family, 'parent' => $parent];
}

describe('child status', function (): void {
    it('lets a satellite admin mark a child of their satellite as graduated', function (): void {
        ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();
        $child = childOf($family, []);
        $admin = staffMember(RoleEnum::Admin, atSatellite($htc));

        actingAs($admin)->put("/ccf/children/{$child->id}/status", ['status' => 'graduated'])->assertRedirect();

        $child->refresh();
        expect($child->status)->toBe(ChildStatusEnum::Graduated)->and($child->status_changed_by)->toBe($admin->id);
    });

    it('refuses a Lifeshaper', function (): void {
        ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();
        $child = childOf($family, []);

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))
            ->put("/ccf/children/{$child->id}/status", ['status' => 'inactive'])->assertNotFound();

        expect($child->fresh()?->status)->toBe(ChildStatusEnum::Active);
    });

    it('refuses an admin of another satellite', function (): void {
        ['org' => $org, 'house' => $house, 'family' => $family] = correctionChurch();
        $child = childOf($family, []);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))
            ->put("/ccf/children/{$child->id}/status", ['status' => 'inactive'])->assertNotFound();
    });
});

describe('correcting the family email (v1 rule)', function (): void {
    it('fixes a mistyped, unconfirmed parent email and sends a new confirmation link', function (): void {
        Notification::fake();
        ['org' => $org, 'family' => $family, 'parent' => $parent] = correctionChurch();
        $version = $parent->credential_version;

        actingAs(staffMember(RoleEnum::Admin, atChurch($org)))
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

        actingAs(staffMember(RoleEnum::Admin, atChurch($org)))
            ->put("/ccf/families/{$family->id}/email", ['email' => 'janice@example.com'])
            ->assertRedirect()->assertSessionHas('error');

        expect($parent->fresh()?->email)->toBe('janice@typo.com');
    });

    it('refuses when the email is already confirmed', function (): void {
        ['org' => $org, 'family' => $family, 'parent' => $parent] = correctionChurch(verified: true);

        actingAs(staffMember(RoleEnum::Admin, atChurch($org)))
            ->put("/ccf/families/{$family->id}/email", ['email' => 'janice@example.com'])
            ->assertSessionHas('error');

        expect($parent->fresh()?->email)->toBe('janice@typo.com');
    });

    it('refuses an email someone else already uses', function (): void {
        ['org' => $org, 'family' => $family] = correctionChurch();
        User::factory()->create(['email' => 'taken@example.com']);

        actingAs(staffMember(RoleEnum::Admin, atChurch($org)))
            ->put("/ccf/families/{$family->id}/email", ['email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');
    });

    it('refuses a Lifeshaper', function (): void {
        ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))
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

    actingAs(staffMember(RoleEnum::Admin, atChurch($org)))
        ->put("/ccf/families/{$family->id}/email", ['email' => 'janice@example.com'])
        ->assertSessionHas('error', 'Email updated, but too many emails went to this address recently, so no link was sent. Use "Send the confirmation link again" in 10 minutes.');

    expect($parent->fresh()?->email)->toBe('janice@example.com');
    Notification::assertNothingSent();
});

/*
 * Staff editing a family (plan §3.2 "Edit family/guardian/child details": church admin; satellite admin for
 * families whose home satellite is theirs; not Lifeshapers). Status stays its own action; email its own flow.
 */

it('lets a satellite admin edit a family of their satellite, and its parent', function (): void {
    ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();

    actingAs(staffMember(RoleEnum::Admin, atSatellite($htc)))
        ->put("/ccf/families/{$family->id}", familyEdit($htc, ['area_text' => 'Karama']))
        ->assertRedirect()->assertSessionHas('success');

    expect($family->fresh()?->area_text)->toBe('Karama')
        ->and(Guardian::query()->findOrFail($family->primary_guardian_id)->last_name)->toBe('Ramos-Lee');
});

it('gives the staff page the edit details only to someone who may edit', function (): void {
    ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();

    actingAs(staffMember(RoleEnum::Admin, atSatellite($htc)))->get("/ccf/families/{$family->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p->where('family.canEdit', true)->has('details')->has('primary'));
    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))->get("/ccf/families/{$family->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p->where('family.canEdit', false)->where('details', null)->where('primary', null));
});

it('refuses a Lifeshaper and an admin of another satellite', function (RoleEnum $role, string $where): void {
    ['org' => $org, 'htc' => $htc, 'house' => $house, 'family' => $family] = correctionChurch();
    $satellite = $where === 'own' ? $htc : $house;

    actingAs(staffMember($role, atSatellite($satellite)))
        ->put("/ccf/families/{$family->id}", familyEdit($htc, ['area_text' => 'Nope']))->assertNotFound();

    expect($family->fresh()?->area_text)->not->toBe('Nope');
})->with([
    'Lifeshaper of the satellite' => [RoleEnum::Lifeshaper, 'own'],
    'admin of another satellite' => [RoleEnum::Admin, 'other'],
]);

it('lets staff who may edit fix a child\'s details, not the status', function (): void {
    ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();
    $child = childOf($family, []);
    $status = $child->status;

    actingAs(staffMember(RoleEnum::Admin, atSatellite($htc)))
        ->put("/ccf/children/{$child->id}", childEdit() + ['status' => 'graduated'])->assertRedirect()->assertSessionHas('success');

    $child->refresh();
    expect($child->nickname)->toBe('Caloy')->and($child->status)->toBe($status);
});

it('refuses a child edit outside the editor\'s satellite', function (): void {
    ['org' => $org, 'house' => $house, 'family' => $family] = correctionChurch();
    $child = childOf($family, ['nickname' => 'Kept']);

    actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))
        ->put("/ccf/children/{$child->id}", childEdit())->assertNotFound();

    expect($child->fresh()?->nickname)->toBe('Kept');
});

/*
 * System notes (plan §4.4 "audited as a system note", §6 "family system notes shown"): what staff changed on a
 * family, by whom and when — written by the app, shown to staff on the family page, never to the parent.
 */
describe('system notes', function (): void {
    it('records an email correction on the family, with who did it', function (): void {
        Notification::fake();
        ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();
        $admin = staffMember(RoleEnum::Admin, atSatellite($htc));
        $admin->forceFill(['name' => 'Sam Admin'])->save();

        actingAs($admin)->put("/ccf/families/{$family->id}/email", ['email' => 'janice@real.com'])->assertSessionHas('success');

        $note = Note::query()->sole();
        expect($note->noteable_type)->toBe('family')
            ->and($note->noteable_id)->toBe($family->id)
            ->and($note->kind)->toBe(NoteKindEnum::System)
            ->and($note->visibility)->toBe(NoteVisibilityEnum::Staff)
            ->and($note->author_id)->toBe($admin->id)
            ->and($note->body)->toBe('Email corrected from janice@typo.com to janice@real.com.');
    });

    it('records a child status change on the child', function (): void {
        ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();
        $child = childOf($family, ['nickname' => 'Amara']);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($htc)))
            ->put("/ccf/children/{$child->id}/status", ['status' => 'inactive']);

        $note = Note::query()->sole();
        expect($note->noteable_type)->toBe('child')->and($note->noteable_id)->toBe($child->id)
            ->and($note->body)->toBe('Amara: status changed from Active to Inactive.');
    });

    it('shows the family\'s and its children\'s notes to staff, newest first, with the author', function (): void {
        ['org' => $org, 'htc' => $htc, 'family' => $family] = correctionChurch();
        $child = childOf($family, []);
        $admin = staffMember(RoleEnum::Admin, atSatellite($htc));
        $admin->forceFill(['name' => 'Sam Admin'])->save();
        Note::factory()->for($family, 'noteable')->create(['organization_id' => $org->id, 'body' => 'Older', 'author_id' => $admin->id, 'created_at' => now()->subDay()]);
        Note::factory()->for($child, 'noteable')->create(['organization_id' => $org->id, 'body' => 'Newer', 'author_id' => null]);

        actingAs($admin)->get("/ccf/families/{$family->id}")->assertInertia(fn (AssertableInertia $p) => $p
            ->has('notes', 2)
            ->where('notes.0.body', 'Newer')
            ->where('notes.0.author', 'NxtGen')
            ->where('notes.1.body', 'Older')
            ->where('notes.1.author', 'Sam Admin'));
    });

    it('never shows staff notes on the parent\'s family page', function (): void {
        ['org' => $org, 'family' => $family, 'parent' => $parent] = correctionChurch();
        Note::factory()->for($family, 'noteable')->create(['organization_id' => $org->id, 'body' => 'Staff only']);

        actingAs($parent)->get('/ccf/family')->assertOk()->assertDontSee('Staff only');
    });
});
