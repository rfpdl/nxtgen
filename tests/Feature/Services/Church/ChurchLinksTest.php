<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\SatelliteLink;
use App\Enums\RoleEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atSatellite;
use function Tests\Support\atTheSameTime;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;
use function Tests\Support\withCommittedWorld;

/*
 * Links (GLOSSARY; Roy 2026-11-06, TODO #31 step 3): a satellite's own addresses on its QR poster — its parents'
 * Facebook group, its WhatsApp group, any link an admin names. A satellite's admins manage its Links — there is no
 * church-wide Role (DECISIONS 2026-11-08), so "an admin of everything" holds the Admin Role at both satellites
 * (AccessScope::canManageLinksAt). The poster shows the satellite's Links as QR codes, for the
 * satellites the person is assigned to.
 */

/** @return array{church: Church, dxb: Satellite, house: Satellite} */
function linksChurch(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);

    return ['church' => $church, 'dxb' => satelliteOf($church, ['name' => 'CCF DXB', 'slug' => 'dxb']), 'house' => satelliteOf($church, ['name' => 'House Church', 'slug' => 'house'])];
}

/** @return list<string> */
function labelsAt(Satellite $satellite): array
{
    return array_values(SatelliteLink::query()->where('satellite_id', $satellite->id)->orderBy('position')->get()->map(fn (SatelliteLink $l): string => $l->label)->all());
}

describe('the Links page', function (): void {
    it('shows an admin each satellite they look after with its Links in order; an admin of both satellites each at its own @story#164', function (): void {
        ['dxb' => $dxb, 'house' => $house] = linksChurch();
        SatelliteLink::factory()->for($dxb)->create(['label' => 'WhatsApp', 'url' => 'https://chat.whatsapp.com/abc', 'position' => 2]);
        SatelliteLink::factory()->for($dxb)->create(['label' => 'Facebook', 'url' => 'https://facebook.com/groups/ccf', 'position' => 1]);

        // DECISIONS 2026-11-08: the page shows only the satellite in its address — an admin of both sees each at its own.
        $churchAdmin = staffMember(RoleEnum::Admin, atSatellite($dxb));
        grantRole($churchAdmin, RoleEnum::Admin, atSatellite($house));
        actingAs($churchAdmin)->get('/dxb/admin/links')->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p->component('Church/Links/Index')
                ->has('satellites', 1)
                ->where('satellites.0.name', 'CCF DXB')
                ->where('satellites.0.links.0.label', 'Facebook')
                ->where('satellites.0.links.1.url', 'https://chat.whatsapp.com/abc')
                ->where('max', 6));
        actingAs($churchAdmin)->get('/house/admin/links')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('satellites', 1)->where('satellites.0.name', 'House Church')->where('satellites.0.links', []));
        $houseAdmin = staffMember(RoleEnum::Admin, atSatellite($house));
        actingAs($houseAdmin)->get('/house/admin/links')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('satellites', 1)->where('satellites.0.name', 'House Church'));
        actingAs($houseAdmin)->get('/dxb/admin/links')->assertNotFound();
    });

    it('is not there for a Lifeshaper @story#165', function (): void {
        ['dxb' => $dxb, 'house' => $house] = linksChurch();

        // DECISIONS 2026-11-08: a Lifeshaper opening an Admin page is sent to the door — one at both satellites too.
        $both = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
        grantRole($both, RoleEnum::Lifeshaper, atSatellite($house));
        actingAs($both)->get('/dxb/admin/links')->assertRedirect('/dxb/serve/door');
    });
});

describe('adding a Link', function (): void {
    it('adds a Link last on a satellite\'s poster @story#166', function (): void {
        ['dxb' => $dxb] = linksChurch();
        SatelliteLink::factory()->for($dxb)->create(['label' => 'Facebook', 'position' => 1]);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))
            ->post('/dxb/admin/links', ['label' => 'Parents\' WhatsApp group', 'url' => 'https://chat.whatsapp.com/abc'])
            ->assertRedirect()->assertSessionHas('success', 'Added "Parents\' WhatsApp group" to CCF DXB\'s poster.');

        expect(labelsAt($dxb))->toBe(['Facebook', 'Parents\' WhatsApp group']);
    });

    it('takes only an https address, and a label @story#167', function (string $url): void {
        ['dxb' => $dxb] = linksChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))
            ->post('/dxb/admin/links', ['label' => '', 'url' => $url])
            ->assertSessionHasErrors(['label', 'url']);

        expect(labelsAt($dxb))->toBe([]);
    })->with([
        'plain http' => ['http://chat.whatsapp.com/abc'],
        'a script' => ['javascript:alert(1)'],
        'not an address' => ['whatsapp group'],
    ]);

    it('holds at most 6 a satellite, so the poster prints on one page @story#167', function (): void {
        ['dxb' => $dxb] = linksChurch();
        foreach (range(1, 6) as $i) {
            SatelliteLink::factory()->for($dxb)->create(['label' => "Link {$i}", 'position' => $i]);
        }

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))
            ->post('/dxb/admin/links', ['label' => 'Seventh', 'url' => 'https://example.com'])
            ->assertSessionHasErrors(['label' => 'CCF DXB\'s poster has 6 links already — remove one first.']);

        expect(labelsAt($dxb))->toHaveCount(6);
    });

    it('adds only at a satellite the admin looks after, in this church @story#167', function (): void {
        ['dxb' => $dxb, 'house' => $house] = linksChurch();
        $other = satelliteOf(church());
        $admin = staffMember(RoleEnum::Admin, atSatellite($house));

        actingAs($admin)->post('/dxb/admin/links', ['label' => 'Nope', 'url' => 'https://example.com'])->assertNotFound();
        expect(SatelliteLink::query()->count())->toBe(0);

        // DECISIONS 2026-11-08: a Link goes to the satellite in the address — a satellite named in the form is not read.
        foreach ([$dxb, $other] as $satellite) {
            actingAs($admin)->post('/house/admin/links', ['satellite_id' => $satellite->id, 'label' => "Not at {$satellite->name}", 'url' => 'https://example.com'])->assertSessionHas('success');
        }
        $adminOfBoth = staffMember(RoleEnum::Admin, atSatellite($dxb));
        grantRole($adminOfBoth, RoleEnum::Admin, atSatellite($house));
        actingAs($adminOfBoth)->post('/dxb/admin/links', ['satellite_id' => $house->id, 'label' => 'At dxb', 'url' => 'https://example.com'])->assertSessionHas('success');

        expect(labelsAt($house))->toBe(["Not at {$dxb->name}", "Not at {$other->name}"])
            ->and(labelsAt($dxb))->toBe(['At dxb'])
            ->and(labelsAt($other))->toBe([]);
    });
});

describe('changing, moving and removing a Link', function (): void {
    it('renames a Link and changes its address @story#168', function (): void {
        ['dxb' => $dxb] = linksChurch();
        $link = SatelliteLink::factory()->for($dxb)->create(['label' => 'Old', 'url' => 'https://old.example']);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))
            ->put("/dxb/admin/links/{$link->id}", ['label' => 'Facebook group', 'url' => 'https://facebook.com/groups/ccf'])
            ->assertRedirect()->assertSessionHas('success', 'Saved "Facebook group".');

        expect([$link->fresh()?->label, $link->fresh()?->url])->toBe(['Facebook group', 'https://facebook.com/groups/ccf']);
    });

    it('changes only a Link at a satellite the admin looks after, and only to an https address @story#169', function (): void {
        ['dxb' => $dxb, 'house' => $house] = linksChurch();
        $link = SatelliteLink::factory()->for($dxb)->create(['label' => 'Kept', 'url' => 'https://kept.example']);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))
            ->put("/house/admin/links/{$link->id}", ['label' => 'Nope', 'url' => 'https://nope.example'])->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))
            ->put("/dxb/admin/links/{$link->id}", ['label' => 'Nope', 'url' => 'https://nope.example'])->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))
            ->put("/dxb/admin/links/{$link->id}", ['label' => 'Nope', 'url' => 'http://nope.example'])->assertSessionHasErrors('url');

        expect([$link->fresh()?->label, $link->fresh()?->url])->toBe(['Kept', 'https://kept.example']);
    });

    it('moves a Link up or down the poster @story#170', function (): void {
        ['dxb' => $dxb] = linksChurch();
        SatelliteLink::factory()->for($dxb)->create(['label' => 'A', 'position' => 1]);
        $b = SatelliteLink::factory()->for($dxb)->create(['label' => 'B', 'position' => 2]);
        $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));

        actingAs($admin)->put("/dxb/admin/links/{$b->id}/move", ['direction' => 'up'])->assertRedirect()->assertSessionHasNoErrors();
        expect(labelsAt($dxb))->toBe(['B', 'A']);

        actingAs($admin)->put("/dxb/admin/links/{$b->id}/move", ['direction' => 'down'])->assertRedirect();
        expect(labelsAt($dxb))->toBe(['A', 'B']);
    });

    it('moves only a Link the admin looks after, up or down @story#171', function (): void {
        ['dxb' => $dxb, 'house' => $house] = linksChurch();
        SatelliteLink::factory()->for($dxb)->create(['label' => 'A', 'position' => 1]);
        $b = SatelliteLink::factory()->for($dxb)->create(['label' => 'B', 'position' => 2]);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->put("/house/admin/links/{$b->id}/move", ['direction' => 'up'])->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->put("/dxb/admin/links/{$b->id}/move", ['direction' => 'sideways'])->assertSessionHasErrors('direction');

        expect(labelsAt($dxb))->toBe(['A', 'B']);
    });

    it('removes a Link from the poster @story#172', function (): void {
        ['dxb' => $dxb] = linksChurch();
        $link = SatelliteLink::factory()->for($dxb)->create(['label' => 'Gone']);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->delete("/dxb/admin/links/{$link->id}")
            ->assertRedirect()->assertSessionHas('success', 'Removed "Gone" from CCF DXB\'s poster.');

        expect(labelsAt($dxb))->toBe([]);
    });

    it('removes only a Link the admin looks after, in this church @story#173', function (): void {
        ['dxb' => $dxb, 'house' => $house] = linksChurch();
        $link = SatelliteLink::factory()->for($dxb)->create(['label' => 'Kept']);
        $theirs = SatelliteLink::factory()->for(satelliteOf(church()))->create();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->delete("/house/admin/links/{$link->id}")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->delete("/dxb/admin/links/{$theirs->id}")->assertNotFound();

        expect(SatelliteLink::query()->count())->toBe(2);
    });
});

describe('two admins at the same moment (Astra) — two connections', function (): void {
    it('never lets two adds past the limit: at five Links, one of two adds is refused @story#167', function (): void {
        ['dxb' => $dxb] = linksChurch();
        foreach (range(1, 5) as $i) {
            SatelliteLink::factory()->for($dxb)->create(['label' => "Link {$i}", 'position' => $i]);
        }
        $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));

        withCommittedWorld(function () use ($admin, $dxb): void {
            $add = function (string $label) use ($admin): Closure {
                return function () use ($admin, $label): bool {
                    actingAs($admin)->post('/dxb/admin/links', ['label' => $label, 'url' => 'https://example.com']);

                    return session()->has('success');
                };
            };

            $added = atTheSameTime($add('Sixth'), $add('Seventh'));

            expect(array_values(array_filter($added)))->toHaveCount(1)
                ->and(SatelliteLink::query()->where('satellite_id', $dxb->id)->count())->toBe(6)
                ->and(SatelliteLink::query()->where('satellite_id', $dxb->id)->distinct()->count('position'))->toBe(6);
        });
    });

    it('keeps every Link in its own place when two are moved at once @story#170', function (): void {
        ['dxb' => $dxb] = linksChurch();
        SatelliteLink::factory()->for($dxb)->create(['label' => 'A', 'position' => 1]);
        $b = SatelliteLink::factory()->for($dxb)->create(['label' => 'B', 'position' => 2]);
        $c = SatelliteLink::factory()->for($dxb)->create(['label' => 'C', 'position' => 3]);
        $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));

        withCommittedWorld(function () use ($admin, $dxb, $b, $c): void {
            $up = fn (SatelliteLink $link): Closure => fn (): int => actingAs($admin)
                ->put("/dxb/admin/links/{$link->id}/move", ['direction' => 'up'])->status();

            atTheSameTime($up($b), $up($c));

            // B first: B A C, then C up → B C A. C first: A C B, then B (now last) up → A B C. Never two in one place.
            $positions = SatelliteLink::query()->where('satellite_id', $dxb->id)->get()->map(fn (SatelliteLink $l): int => $l->position)->all();
            expect(array_unique($positions))->toHaveCount(3)
                ->and(labelsAt($dxb))->toBeIn([['B', 'C', 'A'], ['A', 'B', 'C']]);
        });
    });
});

describe('the poster', function (): void {
    it('shows the satellite\'s Links after the registration QR, each with its own QR code @story#174', function (): void {
        ['dxb' => $dxb] = linksChurch();
        SatelliteLink::factory()->for($dxb)->create(['label' => 'WhatsApp', 'url' => 'https://chat.whatsapp.com/abc', 'position' => 1]);

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get('/dxb/serve/poster')->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p->component('Church/Poster')
                ->where('register_url', url('/dxb/register'))
                ->where('satellite', ['id' => $dxb->id, 'name' => 'CCF DXB', 'slug' => 'dxb'])
                ->missing('satellites')
                ->has('links', 1)
                ->where('links.0.label', 'WhatsApp')
                ->where('links.0.url', 'https://chat.whatsapp.com/abc')
                ->where('links.0.qr', fn (string $qr): bool => str_starts_with($qr, 'data:image/svg+xml;base64,')));
    });

    it('lets someone in two satellites pick, a Lifeshaper or an Admin at both @story#174', function (): void {
        ['dxb' => $dxb, 'house' => $house] = linksChurch();
        SatelliteLink::factory()->for($house)->create(['label' => 'House group']);
        $both = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
        grantRole($both, RoleEnum::Lifeshaper, atSatellite($house));
        // DECISIONS 2026-11-08: no church-wide Role — the old church admin is an Admin at both satellites.
        $churchAdmin = staffMember(RoleEnum::Admin, atSatellite($dxb));
        grantRole($churchAdmin, RoleEnum::Admin, atSatellite($house));

        // DECISIONS 2026-11-08: the poster is the address satellite's — the pick is the address (switched in the bar), not `?satellite=`.
        foreach ([$both, $churchAdmin] as $who) {
            actingAs($who)->get('/house/serve/poster')
                ->assertInertia(fn (AssertableInertia $p) => $p->missing('satellites')->where('satellite.name', 'House Church')->where('register_url', url('/house/register'))->where('links.0.label', 'House group'));
            actingAs($who)->get("/dxb/serve/poster?satellite={$house->id}")
                ->assertInertia(fn (AssertableInertia $p) => $p->where('satellite.name', 'CCF DXB')->where('links', []));
        }
    });

    it('never shows a satellite the person is not assigned to — the first of their own instead @story#175', function (): void {
        ['dxb' => $dxb, 'house' => $house] = linksChurch();
        SatelliteLink::factory()->for($house)->create(['label' => 'House only']);

        $dxbLifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
        // DECISIONS 2026-11-08: at another satellite's address there is no poster for them at all.
        actingAs($dxbLifeshaper)->get('/house/serve/poster')->assertNotFound();
        actingAs($dxbLifeshaper)->get("/dxb/serve/poster?satellite={$house->id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('satellite.name', 'CCF DXB')->where('links', []));
    });
});
