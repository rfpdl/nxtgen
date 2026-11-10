<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Data\Models\User;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Enums\WeekdayEnum;
use App\Notifications\MagicLinkNotification;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\classAdminOf;
use function Tests\Support\familyAt;
use function Tests\Support\helperAt;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;

/*
 * A helper for the day (spec item 18): an admin adds a person on People for one of today's open classes (Roy
 * 2026-11-10: on People); they sign in with the emailed link and may do anything a Lifeshaper may in Serve at that
 * satellite (Roy: "no need to do RBAC now, anything in serve") — never Admin — until the Session closes.
 *
 * It is Saturday 31 Oct 2026, 09:00 in Dubai, throughout.
 */

beforeEach(fn () => travelTo(CarbonImmutable::parse('2026-10-31 09:00:00', 'Asia/Dubai')));

/** @return array{dxb: Satellite, house: Satellite, onePm: Schedule, session: Session, admin: User} */
function helperChurch(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF', 'timezone' => 'Asia/Dubai']);
    $dxb = Satellite::factory()->for($church)->withSquads()->create(['name' => 'CCF DXB', 'slug' => 'dxb']);
    $house = Satellite::factory()->for($church)->withSquads()->create(['name' => 'House Church', 'slug' => 'house']);
    $onePm = scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']);
    scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00', 'ends_at' => '21:00']);

    return ['dxb' => $dxb, 'house' => $house, 'onePm' => $onePm, 'session' => sessionOf($onePm), 'admin' => staffMember(RoleEnum::Admin, atSatellite($dxb))];
}

describe('adding a helper on People', function (): void {
    it('offers an admin "Helper for the day" at each class open now — not one not opened, nor one closed @story#211', function (): void {
        ['dxb' => $dxb, 'session' => $session, 'admin' => $admin] = helperChurch();
        $seven = sessionOf(Schedule::query()->where('name', '7PM')->sole());
        sessionOf(scheduleAt($dxb, ['name' => 'Closed', 'starts_at' => '10:00', 'ends_at' => '11:00']))->update(['closed_at' => now()]);
        scheduleAt($dxb, ['name' => 'Not opened', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '16:00', 'ends_at' => '17:00']);

        // Two open at once: each named with its class and satellite, read in one go (lazy loading is refused).
        actingAs($admin)->get('/dxb/admin/people')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('grant_options', fn (Collection $options): bool => $options->where('role', 'helper')->values()->all() === [
                ['label' => 'Helper for the day · CCF DXB · 1PM today', 'role' => 'helper', 'scope_type' => 'session', 'scope_id' => $session->id],
                ['label' => 'Helper for the day · CCF DXB · 7PM today', 'role' => 'helper', 'scope_type' => 'session', 'scope_id' => $seven->id],
            ]));
    });

    it('adds the helper, gives the Role for that Session only and emails a sign-in link @story#211', function (): void {
        Notification::fake();
        ['session' => $session, 'admin' => $admin] = helperChurch();

        actingAs($admin)->post('/dxb/admin/people', ['name' => 'Hana Helper', 'email' => 'hana@example.com', 'role' => 'helper', 'scope_type' => 'session', 'scope_id' => $session->id])
            ->assertSessionHas('success', 'hana@example.com is now Helper for the day · CCF DXB · 1PM today. We emailed them a sign-in link.');

        $hana = User::query()->where('email', 'hana@example.com')->sole();
        expect(RoleAssignment::query()->where('user_id', $hana->id)->sole()->only(['role', 'scope_type', 'scope_id']))
            ->toBe(['role' => RoleEnum::Helper, 'scope_type' => ScopeTypeEnum::Session, 'scope_id' => $session->id]);
        Notification::assertSentOnDemand(MagicLinkNotification::class, fn (MagicLinkNotification $n, array $channels, AnonymousNotifiable $to): bool => $n->purpose === MagicLinkPurposeEnum::Login
            && $n->invitedAs === 'Helper for the day · CCF DXB · 1PM today'
            && $to->routes['mail'] === 'hana@example.com');
        actingAs($admin)->get('/dxb/admin/people')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('people', fn (Collection $people): bool => data_get($people->firstWhere('name', 'Hana Helper'), 'roles.0.label') === 'Helper for the day · CCF DXB · 1PM today'));
    });

    it('refuses a helper for a class not open, another satellite\'s, a Lifeshaper or a class admin adding one @story#212', function (): void {
        ['dxb' => $dxb, 'house' => $house, 'onePm' => $onePm, 'session' => $session, 'admin' => $admin] = helperChurch();
        $closed = sessionOf(Schedule::query()->where('name', '7PM')->sole());
        $closed->update(['closed_at' => now()]);
        $atHouse = sessionOf(scheduleAt($house, ['name' => '10AM']));
        $form = fn (int $id): array => ['name' => 'Hana Helper', 'email' => 'hana@example.com', 'role' => 'helper', 'scope_type' => 'session', 'scope_id' => $id];

        actingAs($admin)->post('/dxb/admin/people', $form($closed->id))->assertNotFound();
        actingAs($admin)->post('/dxb/admin/people', $form($atHouse->id))->assertNotFound();
        actingAs($admin)->post('/dxb/admin/people', [...$form($session->id), 'role' => 'admin'])->assertNotFound();
        actingAs($admin)->post('/dxb/admin/people', [...$form($session->id), 'scope_type' => 'satellite', 'scope_id' => $dxb->id, 'role' => 'helper'])->assertNotFound();
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->post('/dxb/admin/people', $form($session->id))->assertNotFound();
        actingAs(classAdminOf($onePm))->post('/dxb/admin/people', $form($session->id))->assertNotFound();

        expect(User::query()->where('email', 'hana@example.com')->exists())->toBeFalse();
    });
});

describe('a helper\'s access', function (): void {
    it('lets the helper into anything in Serve at that satellite while the Session is open — never Admin @story#213', function (): void {
        ['dxb' => $dxb, 'session' => $session] = helperChurch();
        $helper = helperAt($session);
        $seven = sessionOf(Schedule::query()->where('name', '7PM')->sole());
        $family = familyAt($dxb);

        actingAs($helper)->get("/{$session->code}/checkin")->assertOk();
        actingAs($helper)->get("/{$session->code}/whos-in")->assertOk();
        actingAs($helper)->get("/{$seven->code}/checkin")->assertOk();   // every open door there (Roy)
        actingAs($helper)->get('/dxb/serve')->assertOk();
        actingAs($helper)->get('/dxb/serve/families')->assertOk();
        actingAs($helper)->get("/dxb/serve/families/{$family->id}")->assertOk();
        actingAs($helper)->get('/dxb/admin/families')->assertRedirect('/dxb/serve');
        actingAs($helper)->get('/dxb/admin/people')->assertRedirect('/dxb/serve');
        actingAs($helper)->get('/house/serve')->assertNotFound();
    });

    it('ends when the Session closes — and at midnight @story#214', function (): void {
        ['session' => $session] = helperChurch();
        $helper = helperAt($session);
        $session->update(['closed_at' => now()]);

        actingAs($helper)->get("/{$session->code}/checkin")->assertNotFound();
        actingAs($helper)->get('/dxb/serve')->assertNotFound();
        actingAs($helper)->get('/dxb/serve/families')->assertNotFound();

        $session->update(['closed_at' => null]);
        travelTo(CarbonImmutable::parse('2026-11-01 00:00:05', 'Asia/Dubai'));
        actingAs($helper)->get('/dxb/serve')->assertNotFound();
    });

    it('lands the helper on their satellite\'s Serve after sign-in while it is open, and nowhere once it closes @story#213 @story#214', function (): void {
        ['session' => $session] = helperChurch();
        $helper = helperAt($session);

        actingAs($helper)->get('/home')->assertRedirect('/dxb');   // which opens Serve, for someone who is no admin

        $session->update(['closed_at' => now()]);
        actingAs($helper)->get('/home')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/NothingYet'));
    });
});

describe('what a helper may not do, and removing one', function (): void {
    it('lets a helper give no Role, open or close no class, and remove no Role @story#212 @story#213', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'session' => $session] = helperChurch();
        $helper = helperAt($session);
        $lifeshaperRole = RoleAssignment::query()->where('user_id', staffMember(RoleEnum::Lifeshaper, atSatellite($dxb))->id)->sole();

        actingAs($helper)->post('/dxb/admin/people', ['name' => 'X', 'email' => 'x@example.com', 'role' => 'helper', 'scope_type' => 'session', 'scope_id' => $session->id])->assertNotFound();
        actingAs($helper)->delete("/dxb/admin/people/roles/{$lifeshaperRole->id}")->assertNotFound();
        actingAs($helper)->put("/{$session->code}/close")->assertNotFound();
        actingAs($helper)->post("/dxb/serve/classes/{$onePm->id}/session")->assertNotFound();

        expect([$session->fresh()?->closed_at, $lifeshaperRole->fresh()?->is_active, User::query()->where('email', 'x@example.com')->exists()])->toBe([null, true, false]);
    });

    it('lets the satellite\'s admin remove a helper — not a Lifeshaper, nor another satellite\'s admin — and the helper loses access at once @story#211 @story#212', function (): void {
        ['dxb' => $dxb, 'house' => $house, 'session' => $session, 'admin' => $admin] = helperChurch();
        $helper = helperAt($session);
        $role = RoleAssignment::query()->where('user_id', $helper->id)->sole();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->delete("/dxb/admin/people/roles/{$role->id}")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->delete("/house/admin/people/roles/{$role->id}")->assertNotFound();
        expect($role->fresh()?->is_active)->toBeTrue();

        actingAs($admin)->delete("/dxb/admin/people/roles/{$role->id}")->assertSessionHas('success', 'Role removed.');
        expect($role->fresh()?->is_active)->toBeFalse();
        actingAs($helper)->get("/{$session->code}/checkin")->assertNotFound();
    });

    it('is not found at another church\'s satellite', function (): void {
        ['session' => $session] = helperChurch();
        $elsewhere = Satellite::factory()->for(church(['slug' => 'other']))->create(['slug' => 'other']);

        actingAs(helperAt($session))->get("/{$elsewhere->slug}/serve")->assertNotFound();
    });
});
