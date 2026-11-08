<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Note;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withoutExceptionHandling;
use function Tests\Support\atSatellite;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * Children's photos (plan §5 + Roy 2026-10-16): a parent OR a Lifeshaper may take or upload one — but only once
 * consent is recorded (by the parent, or by a Lifeshaper the parent told). Only the parent takes it back, which
 * HIDES the photo until they agree again. Photos are private: seen only by the child's own family and staff who
 * can see the family, through short-lived signed links.
 */

/** @return array{church: Church, htc: Satellite, house: Satellite, family: Family, guardian: User, child: Child} */
function photoFamily(): array
{
    $church = church(['slug' => 'ccf']);
    $htc = satelliteOf($church, ['slug' => 'htc']);
    $house = satelliteOf($church, ['slug' => 'house']);
    $family = familyAt($htc);
    $guardian = User::factory()->create();
    $g = guardianOf($family, $guardian);
    $child = childOf($family, ['nickname' => 'Amara']);

    return ['church' => $church, 'htc' => $htc, 'house' => $house, 'family' => $family, 'guardian' => $guardian, 'child' => $child];
}

function photoFile(): UploadedFile
{
    return new UploadedFile(base_path('tests/Fixtures/phone-photo-rotated-gps.jpg'), 'IMG_0001.jpg', 'image/jpeg', null, true);
}

beforeEach(fn () => Storage::fake('photos'));

describe('a parent', function (): void {
    it('agrees and adds a photo in one go @story#38', function (): void {
        ['guardian' => $guardian, 'child' => $child] = photoFamily();

        actingAs($guardian)->post("/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true])
            ->assertRedirect()->assertSessionHas('success');

        $child->refresh();
        expect($child->photo_path)->not->toBeNull()
            ->and($child->photo_consent_at)->not->toBeNull()
            ->and($child->photo_consent_by)->toBe($guardian->id);
    });

    it('cannot add a photo without agreeing @story#39', function (): void {
        ['guardian' => $guardian, 'child' => $child] = photoFamily();

        actingAs($guardian)->post("/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => false])
            ->assertSessionHasErrors(['consent' => 'Please agree to the photo first.']);

        expect($child->fresh()?->photo_path)->toBeNull();
    });

    it('only sends photos', function (): void {
        ['guardian' => $guardian, 'child' => $child] = photoFamily();

        actingAs($guardian)->post("/family/children/{$child->id}/photo", ['photo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'), 'consent' => true])
            ->assertSessionHasErrors('photo');
    });

    // Roy's decision, 2026-10-16.
    it('takes consent back, which HIDES the photo — and saying yes again brings it back @story#37', function (): void {
        ['church' => $church, 'htc' => $htc, 'guardian' => $guardian, 'child' => $child] = photoFamily();
        actingAs($guardian)->post("/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true]);
        $path = (string) $child->fresh()?->photo_path;
        $url = URL::temporarySignedRoute('photos.child', now()->addMinutes(5), ['child' => $child->id]);

        actingAs($guardian)->delete("/family/children/{$child->id}/photo-consent")->assertSessionHas('success');

        $child->refresh();
        expect($child->photo_consent_at)->toBeNull()->and($child->photo_path)->toBe($path);
        Storage::disk('photos')->assertExists($path);
        // Hidden from everyone: no link, and the photo itself refuses — for the family and for staff.
        actingAs($guardian)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p->where('family.children.0.photo_url', null));
        actingAs($guardian)->get($url)->assertNotFound();
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))->get($url)->assertNotFound();

        actingAs($guardian)->put("/family/children/{$child->id}/photo-consent");
        actingAs($guardian)->get($url)->assertOk();
    });

    it('cannot touch another family\'s child @story#39', function (): void {
        ['guardian' => $guardian] = photoFamily();
        $stranger = Child::factory()->create();

        actingAs($guardian)->post("/family/children/{$stranger->id}/photo", ['photo' => photoFile(), 'consent' => true])->assertNotFound();
    });
});

// Found in review 4.
describe('the consent history', function (): void {
    it('notes every consent change on the child — given by the parent, then withdrawn — and keeps the notes @story#36 @story#37', function (): void {
        ['guardian' => $guardian, 'child' => $child] = photoFamily();
        $guardian->forceFill(['name' => 'Janice Ramos'])->save();

        actingAs($guardian)->put("/family/children/{$child->id}/photo-consent");
        actingAs($guardian)->delete("/family/children/{$child->id}/photo-consent");

        expect(Note::query()->where('noteable_type', 'child')->where('noteable_id', $child->id)->orderBy('id')->pluck('body')->all())->toBe([
            'Amara: photo consent given by Janice Ramos.',
            'Amara: photo consent withdrawn by Janice Ramos — the photo is hidden.',
        ]);
    });

    // Found in review 4, round 2.
    it('never records consent without its note — both or neither', function (string $who): void {
        ['church' => $church, 'htc' => $htc, 'guardian' => $guardian, 'child' => $child] = photoFamily();
        Note::creating(fn () => throw new RuntimeException('note store down'));
        withoutExceptionHandling();

        $request = fn () => $who === 'parent'
            ? actingAs($guardian)->put("/family/children/{$child->id}/photo-consent")
            : actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))->put("/htc/serve/children/{$child->id}/photo-consent");

        expect($request)->toThrow(RuntimeException::class, 'note store down');
        expect($child->fresh()?->photo_consent_at)->toBeNull();
    })->with(['parent', 'staff']);

    it('notes consent given together with the first photo', function (): void {
        ['guardian' => $guardian, 'child' => $child] = photoFamily();
        $guardian->forceFill(['name' => 'Janice Ramos'])->save();

        actingAs($guardian)->post("/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true]);

        expect(Note::query()->where('noteable_id', $child->id)->sole()->body)->toBe('Amara: photo consent given by Janice Ramos.');
    });
});

// Found in review 3.
describe('consent first, photo later', function (): void {
    it('lets a parent agree without a photo, so a Lifeshaper can take the first one at church @story#36', function (): void {
        ['church' => $church, 'htc' => $htc, 'guardian' => $guardian, 'child' => $child] = photoFamily();

        actingAs($guardian)->put("/family/children/{$child->id}/photo-consent")->assertRedirect()->assertSessionHas('success');
        expect($child->fresh()?->photo_consent_by)->toBe($guardian->id)->and($child->fresh()?->photo_path)->toBeNull();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))
            ->post("/htc/serve/children/{$child->id}/photo", ['photo' => photoFile()])->assertSessionHas('success');
        expect($child->fresh()?->photo_path)->not->toBeNull();
    });

    it('never shows a photo, nor gives a link to one, once consent is gone @story#41', function (): void {
        ['guardian' => $guardian, 'child' => $child] = photoFamily();
        actingAs($guardian)->post("/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true]);
        $url = URL::temporarySignedRoute('photos.child', now()->addMinutes(5), ['child' => $child->id]);
        // The state an upload finishing after a withdrawal could leave: a stored photo, no consent.
        $child->forceFill(['photo_consent_at' => null, 'photo_consent_by' => null])->save();

        actingAs($guardian)->get($url)->assertNotFound();
        actingAs($guardian)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p->where('family.children.0.photo_url', null));
    });
});

describe('a Lifeshaper', function (): void {
    it('adds a photo once the parent has agreed @story#68', function (): void {
        ['church' => $church, 'htc' => $htc, 'child' => $child, 'guardian' => $guardian] = photoFamily();
        $child->forceFill(['photo_consent_at' => now(), 'photo_consent_by' => $guardian->id])->save();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))
            ->post("/htc/serve/children/{$child->id}/photo", ['photo' => photoFile()])->assertSessionHas('success');

        expect($child->fresh()?->photo_path)->not->toBeNull();
    });

    it('cannot add one before consent is recorded — sending "consent" with the photo does not count @story#69', function (): void {
        ['church' => $church, 'htc' => $htc, 'child' => $child] = photoFamily();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))
            ->post("/htc/serve/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true])
            ->assertSessionHas('error', 'The parent has not agreed to a photo yet. If they say yes to you, record it first.');

        expect($child->fresh()?->photo_path)->toBeNull()->and($child->fresh()?->photo_consent_at)->toBeNull();
    });

    // Roy's decision, 2026-10-16.
    it('records that the parent said yes in person, with who recorded it, then takes the photo @story#66', function (): void {
        ['church' => $church, 'htc' => $htc, 'child' => $child] = photoFamily();
        $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($htc));
        $lifeshaper->forceFill(['name' => 'Leo Lifeshaper'])->save();

        actingAs($lifeshaper)->put("/htc/serve/children/{$child->id}/photo-consent")->assertSessionHas('success');
        actingAs($lifeshaper)->post("/htc/serve/children/{$child->id}/photo", ['photo' => photoFile()])->assertSessionHas('success');

        $child->refresh();
        expect($child->photo_consent_by)->toBe($lifeshaper->id)
            ->and($child->photo_path)->not->toBeNull()
            ->and(Note::query()->where('noteable_type', 'child')->where('noteable_id', $child->id)->sole()->body)
            ->toBe('Amara: photo consent recorded by Leo Lifeshaper — the parent said yes in person.');
    });

    it('cannot record consent for a family they cannot see @story#67', function (): void {
        ['church' => $church, 'house' => $house, 'child' => $child] = photoFamily();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($house)))
            ->put("/house/serve/children/{$child->id}/photo-consent")->assertNotFound();

        expect($child->fresh()?->photo_consent_at)->toBeNull();
    });

    it('cannot add one for a family they cannot see @story#69', function (): void {
        ['church' => $church, 'house' => $house, 'child' => $child, 'guardian' => $guardian] = photoFamily();
        $child->forceFill(['photo_consent_at' => now(), 'photo_consent_by' => $guardian->id])->save();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($house)))
            ->post("/house/serve/children/{$child->id}/photo", ['photo' => photoFile()])->assertNotFound();
    });
});

describe('seeing a photo', function (): void {
    it('is a short-lived signed link, for the family and for staff who can see the family @story#40', function (): void {
        ['church' => $church, 'htc' => $htc, 'house' => $house, 'guardian' => $guardian, 'child' => $child] = photoFamily();
        actingAs($guardian)->post("/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true]);

        $url = '';
        actingAs($guardian)->get('/family')->assertInertia(function (AssertableInertia $p) use (&$url) {
            $p->where('family.children.0.photo_consent', true)->where('family.children.0.photo_url', function (string $u) use (&$url): bool {
                $url = $u;

                return str_contains($u, 'signature=') && str_contains($u, 'expires=');
            });
        });

        actingAs($guardian)->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))->get($url)->assertOk();
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($house)))->get($url)->assertNotFound();
        actingAs(User::factory()->create())->get($url)->assertNotFound();
        actingAs($guardian)->get((string) strtok($url, '?'))->assertForbidden();   // unsigned
        Auth::logout();
        get($url)->assertRedirect('/login');                                    // signed, but nobody signed in
    });

    // Astra 2026-11-08: the signature is checked before the child is looked up, so an unsigned address says the same
    // for a child of this church, of another church, and for no child at all — it confirms no child id.
    it('refuses an unsigned photo address the same way whether the child exists or not @story#41', function (): void {
        ['child' => $child] = photoFamily();
        $theirs = childOf(familyAt(satelliteOf(church())));
        $nobody = $theirs->id + 1000;

        foreach ([$child->id, $theirs->id, $nobody] as $id) {
            actingAs(User::factory()->create())->get("/photos/children/{$id}")->assertForbidden();
        }
    });

    it('gives no link when there is no photo @story#41', function (): void {
        ['guardian' => $guardian] = photoFamily();

        actingAs($guardian)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p
            ->where('family.children.0.photo_url', null)->where('family.children.0.photo_consent', false));
    });
});

/*
 * Deleting a photo for good (TODO #25, SECURITY #19): a parent asking for erasure — withdrawing consent only hides
 * the photo. The parent from their own page; an admin who may edit the family, in Admin. Never a Lifeshaper.
 */
describe('deleting a photo for good', function (): void {
    it('lets a parent delete their child\'s photo for good — even one hidden when consent was taken back @story#176', function (): void {
        ['guardian' => $guardian, 'child' => $child] = photoFamily();
        actingAs($guardian)->post("/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true]);
        $path = (string) $child->fresh()?->photo_path;
        actingAs($guardian)->delete("/family/children/{$child->id}/photo-consent");
        actingAs($guardian)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p
            ->where('family.children.0.photo_url', null)->where('family.children.0.has_photo', true));

        actingAs($guardian)->delete("/family/children/{$child->id}/photo")
            ->assertRedirect()->assertSessionHas('success', 'Amara\'s photo is deleted for good.');

        Storage::disk('photos')->assertMissing($path);
        expect($child->fresh()?->photo_path)->toBeNull()
            ->and(Note::query()->where('body', 'like', '%photo deleted for good%')->exists())->toBeTrue();
        actingAs($guardian)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p->where('family.children.0.has_photo', false));
    });

    it('deletes only their own child\'s, and says plainly when there is nothing to delete @story#177', function (): void {
        ['guardian' => $guardian, 'child' => $child] = photoFamily();
        $stranger = Child::factory()->create(['photo_path' => 'children/9/x.jpg']);

        actingAs($guardian)->delete("/family/children/{$stranger->id}/photo")->assertNotFound();
        actingAs($guardian)->delete("/family/children/{$child->id}/photo")->assertSessionHas('error', 'Amara has no photo to delete.');

        expect($stranger->fresh()?->photo_path)->toBe('children/9/x.jpg');
    });

    it('lets an admin who may edit the family delete a child\'s photo for good, in Admin @story#178', function (): void {
        ['htc' => $htc, 'guardian' => $guardian, 'child' => $child] = photoFamily();
        actingAs($guardian)->post("/family/children/{$child->id}/photo", ['photo' => photoFile(), 'consent' => true]);
        $path = (string) $child->fresh()?->photo_path;

        actingAs(staffMember(RoleEnum::Admin, atSatellite($htc)))->delete("/htc/admin/children/{$child->id}/photo")
            ->assertRedirect()->assertSessionHas('success', 'Amara\'s photo is deleted for good.');

        Storage::disk('photos')->assertMissing($path);
        expect($child->fresh()?->photo_path)->toBeNull();
    });

    it('is not for a Lifeshaper, another satellite\'s admin, or anyone in Serve @story#179', function (): void {
        ['htc' => $htc, 'house' => $house, 'child' => $child] = photoFamily();
        $child->forceFill(['photo_path' => 'children/1/kept.jpg', 'photo_consent_at' => now()])->save();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($htc)))->delete("/htc/admin/children/{$child->id}/photo")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->delete("/house/admin/children/{$child->id}/photo")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($htc)))->delete("/htc/serve/children/{$child->id}/photo")->assertStatus(405);

        expect($child->fresh()?->photo_path)->toBe('children/1/kept.jpg');
    });
});
