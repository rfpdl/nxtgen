<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Note;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
 * Children's photos (plan §5 + Roy 2026-10-16): a parent OR a Lifeshaper may take or upload one — but only once
 * the parent has agreed. Only a parent can agree (or take it back, which removes the photo). Photos are private:
 * seen only by the child's own family and staff who can see the family, through short-lived signed links.
 */

/** @return array{org: Organization, htc: Branch, house: Branch, family: Family, parent: User, child: Child} */
function photoFamily(): array
{
    $org = Organization::factory()->create(['slug' => 'ccf']);
    $htc = Branch::factory()->for($org)->create();
    $house = Branch::factory()->for($org)->create();
    $family = Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => $htc->id]);
    $parent = User::factory()->create();
    $g = Guardian::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'user_id' => $parent->id]);
    $family->forceFill(['primary_guardian_id' => $g->id])->save();
    $child = Child::factory()->create(['family_id' => $family->id, 'organization_id' => $org->id, 'nickname' => 'Amara']);

    return ['org' => $org, 'htc' => $htc, 'house' => $house, 'family' => $family, 'parent' => $parent, 'child' => $child];
}

function photoStaff(Organization $org, RoleEnum $role, ScopeTypeEnum $type, int $id): User
{
    $u = User::factory()->create();
    RoleAssignment::factory()->create(['organization_id' => $org->id, 'user_id' => $u->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $id]);

    return $u;
}

function photoFile(): UploadedFile
{
    return new UploadedFile(base_path('tests/Fixtures/phone-photo-rotated-gps.jpg'), 'IMG_0001.jpg', 'image/jpeg', null, true);
}

beforeEach(fn () => Storage::fake('photos'));

describe('a parent', function (): void {
    it('agrees and adds a photo in one go', function (): void {
        ['parent' => $parent, 'child' => $child] = photoFamily();

        actingAs($parent)->post("/ccf/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true])
            ->assertRedirect()->assertSessionHas('success');

        $child->refresh();
        expect($child->photo_path)->not->toBeNull()
            ->and($child->photo_consent_at)->not->toBeNull()
            ->and($child->photo_consent_by)->toBe($parent->id);
    });

    it('cannot add a photo without agreeing', function (): void {
        ['parent' => $parent, 'child' => $child] = photoFamily();

        actingAs($parent)->post("/ccf/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => false])
            ->assertSessionHasErrors(['consent' => 'Please agree to the photo first.']);

        expect($child->fresh()?->photo_path)->toBeNull();
    });

    it('only sends photos', function (): void {
        ['parent' => $parent, 'child' => $child] = photoFamily();

        actingAs($parent)->post("/ccf/family/children/{$child->id}/photo", ['photo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'), 'consent' => true])
            ->assertSessionHasErrors('photo');
    });

    it('takes consent back, which removes the photo', function (): void {
        ['parent' => $parent, 'child' => $child] = photoFamily();
        actingAs($parent)->post("/ccf/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true]);
        $path = (string) $child->fresh()?->photo_path;

        actingAs($parent)->delete("/ccf/family/children/{$child->id}/photo-consent")->assertSessionHas('success');

        $child->refresh();
        expect($child->photo_path)->toBeNull()->and($child->photo_consent_at)->toBeNull()->and($child->photo_consent_by)->toBeNull();
        Storage::disk('photos')->assertMissing($path);
    });

    it('cannot touch another family\'s child', function (): void {
        ['parent' => $parent] = photoFamily();
        $stranger = Child::factory()->create();

        actingAs($parent)->post("/ccf/family/children/{$stranger->id}/photo", ['photo' => photoFile(), 'consent' => true])->assertNotFound();
    });
});

describe('consent first, photo later (review 3)', function (): void {
    it('lets a parent agree without a photo, so a Lifeshaper can take the first one at church', function (): void {
        ['org' => $org, 'htc' => $htc, 'parent' => $parent, 'child' => $child] = photoFamily();

        actingAs($parent)->put("/ccf/family/children/{$child->id}/photo-consent")->assertRedirect()->assertSessionHas('success');
        expect($child->fresh()?->photo_consent_by)->toBe($parent->id)->and($child->fresh()?->photo_path)->toBeNull();

        actingAs(photoStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))
            ->post("/ccf/children/{$child->id}/photo", ['photo' => photoFile()])->assertSessionHas('success');
        expect($child->fresh()?->photo_path)->not->toBeNull();
    });

    it('never shows a photo, nor gives a link to one, once consent is gone', function (): void {
        ['parent' => $parent, 'child' => $child] = photoFamily();
        actingAs($parent)->post("/ccf/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true]);
        $url = URL::temporarySignedRoute('photos.child', now()->addMinutes(5), ['child' => $child->id]);
        // The state an upload finishing after a withdrawal could leave: a stored photo, no consent.
        $child->forceFill(['photo_consent_at' => null, 'photo_consent_by' => null])->save();

        actingAs($parent)->get($url)->assertNotFound();
        actingAs($parent)->get('/ccf/family')->assertInertia(fn (AssertableInertia $p) => $p->where('family.children.0.photoUrl', null));
    });
});

describe('a Lifeshaper', function (): void {
    it('adds a photo once the parent has agreed', function (): void {
        ['org' => $org, 'htc' => $htc, 'child' => $child, 'parent' => $parent] = photoFamily();
        $child->forceFill(['photo_consent_at' => now(), 'photo_consent_by' => $parent->id])->save();

        actingAs(photoStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))
            ->post("/ccf/children/{$child->id}/photo", ['photo' => photoFile()])->assertSessionHas('success');

        expect($child->fresh()?->photo_path)->not->toBeNull();
    });

    it('cannot add one before consent is recorded — sending "consent" with the photo does not count', function (): void {
        ['org' => $org, 'htc' => $htc, 'child' => $child] = photoFamily();

        actingAs(photoStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))
            ->post("/ccf/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true])
            ->assertSessionHas('error', 'The parent has not agreed to a photo yet. If they say yes to you, record it first.');

        expect($child->fresh()?->photo_path)->toBeNull()->and($child->fresh()?->photo_consent_at)->toBeNull();
    });

    it('records that the parent said yes in person, with who recorded it, then takes the photo (Roy 2026-10-16)', function (): void {
        ['org' => $org, 'htc' => $htc, 'child' => $child] = photoFamily();
        $lifeshaper = photoStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);
        $lifeshaper->forceFill(['name' => 'Leo Lifeshaper'])->save();

        actingAs($lifeshaper)->put("/ccf/children/{$child->id}/photo-consent")->assertSessionHas('success');
        actingAs($lifeshaper)->post("/ccf/children/{$child->id}/photo", ['photo' => photoFile()])->assertSessionHas('success');

        $child->refresh();
        expect($child->photo_consent_by)->toBe($lifeshaper->id)
            ->and($child->photo_path)->not->toBeNull()
            ->and(Note::query()->where('noteable_type', 'child')->where('noteable_id', $child->id)->sole()->body)
            ->toBe('Amara: photo consent recorded by Leo Lifeshaper — the parent said yes in person.');
    });

    it('cannot record consent for a family they cannot see', function (): void {
        ['org' => $org, 'house' => $house, 'child' => $child] = photoFamily();

        actingAs(photoStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $house->id))
            ->put("/ccf/children/{$child->id}/photo-consent")->assertNotFound();

        expect($child->fresh()?->photo_consent_at)->toBeNull();
    });

    it('cannot add one for a family they cannot see', function (): void {
        ['org' => $org, 'house' => $house, 'child' => $child, 'parent' => $parent] = photoFamily();
        $child->forceFill(['photo_consent_at' => now(), 'photo_consent_by' => $parent->id])->save();

        actingAs(photoStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $house->id))
            ->post("/ccf/children/{$child->id}/photo", ['photo' => photoFile()])->assertNotFound();
    });
});

describe('seeing a photo', function (): void {
    it('is a short-lived signed link, for the family and for staff who can see the family', function (): void {
        ['org' => $org, 'htc' => $htc, 'house' => $house, 'parent' => $parent, 'child' => $child] = photoFamily();
        actingAs($parent)->post("/ccf/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true]);

        $url = '';
        actingAs($parent)->get('/ccf/family')->assertInertia(function (AssertableInertia $p) use (&$url) {
            $p->where('family.children.0.photoConsent', true)->where('family.children.0.photoUrl', function (string $u) use (&$url): bool {
                $url = $u;

                return str_contains($u, 'signature=') && str_contains($u, 'expires=');
            });
        });

        actingAs($parent)->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
        actingAs(photoStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))->get($url)->assertOk();
        actingAs(photoStaff($org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $house->id))->get($url)->assertNotFound();
        actingAs(User::factory()->create())->get($url)->assertNotFound();
        actingAs($parent)->get((string) strtok($url, '?'))->assertForbidden();   // unsigned
        Auth::logout();
        get($url)->assertRedirect('/login');                                    // signed, but nobody signed in
    });

    it('gives no link when there is no photo', function (): void {
        ['parent' => $parent] = photoFamily();

        actingAs($parent)->get('/ccf/family')->assertInertia(fn (AssertableInertia $p) => $p
            ->where('family.children.0.photoUrl', null)->where('family.children.0.photoConsent', false));
    });
});
