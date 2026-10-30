<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * Cards (name cards 03, DECISIONS 2026-10-29): admins see the Card requests of their satellites' families by step and
 * move them — mark paid, approve (only paid or free), refuse (with a reason), mark printed (one, or all approved);
 * anyone who may open the family marks a printed card given, at the desk. Each move happens once: a stale one says
 * where the request is now. The who-may grid is AccessMatrixTest's; this file is the rules.
 */

/** @return array{church: Church, dxb: Satellite, house: Satellite, amara: Child, admin: User} */
function cardsChurch(): array
{
    $church = church(['slug' => 'ccf']);
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    $amara = childOf(familyAt($dxb), ['first_name' => 'Amara', 'middle_name' => null, 'last_name' => 'Reyes', 'nickname' => 'Amara']);

    return ['church' => $church, 'dxb' => $dxb, 'house' => $house, 'amara' => $amara, 'admin' => staffMember(RoleEnum::Admin, atSatellite($dxb))];
}

describe('the Cards page', function (): void {
    it('lists a tab\'s requests of the admin\'s satellites — whose, which family, why, the fee @story#134', function (): void {
        ['dxb' => $dxb, 'house' => $house, 'amara' => $amara, 'admin' => $admin] = cardsChurch();
        $lost = CardRequest::factory()->for($amara)->create(['note' => 'Lost at the park']);
        CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]);
        CardRequest::factory()->for(childOf(familyAt($house)))->create();

        actingAs($admin)->get('/ccf/card-requests')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Church/Cards/Index')
            ->where('tab', 'asked')
            ->where('pagination.total', 1)
            ->where('requests.0.id', $lost->id)
            ->where('requests.0.child_name', 'Amara Reyes')
            ->where('requests.0.reason_label', 'Lost')
            ->where('requests.0.note', 'Lost at the park')
            ->where('requests.0.fee_label', 'AED 10 · not paid')
            ->where('requests.0.moves', ['mark_paid', 'refuse'])
            ->where('enums.card_tab.3.label', 'Given or refused'));
        actingAs($admin)->get('/ccf/card-requests?tab=approved')->assertInertia(fn (AssertableInertia $p) => $p->where('tab', 'approved')->where('pagination.total', 1));
        actingAs($admin)->get('/ccf/card-requests?tab=nonsense')->assertInertia(fn (AssertableInertia $p) => $p->where('tab', 'asked'));
    });

    it('shows a church admin every satellite\'s requests, a satellite admin only their own; a Lifeshaper has no page @story#135', function (): void {
        ['church' => $church, 'house' => $house, 'amara' => $amara] = cardsChurch();
        $dxbRequest = CardRequest::factory()->for($amara)->create();
        $houseRequest = CardRequest::factory()->for(childOf(familyAt($house)))->create();

        $lists = fn (User $who, array $ids) => actingAs($who)->get('/ccf/card-requests')->assertInertia(fn (AssertableInertia $p) => $p
            ->where('requests', fn (Collection $rows): bool => $rows->pluck('id')->all() === $ids));

        $lists(staffMember(RoleEnum::Admin, atChurch($church)), [$dxbRequest->id, $houseRequest->id]);
        $lists(staffMember(RoleEnum::Admin, atSatellite($house)), [$houseRequest->id]);
        actingAs(staffMember(RoleEnum::Lifeshaper, atChurch($church)))->get('/ccf/card-requests')->assertNotFound();
    });
});

describe('searching Cards', function (): void {
    it('finds a child\'s requests by name — every word on the child, in the tab chosen @story#134', function (): void {
        ['dxb' => $dxb, 'amara' => $amara, 'admin' => $admin] = cardsChurch();
        $hers = CardRequest::factory()->for($amara)->create();
        CardRequest::factory()->for(childOf(familyAt($dxb), ['first_name' => 'Amara', 'last_name' => 'Cruz', 'nickname' => 'Ams']))->create();
        CardRequest::factory()->for(childOf(familyAt($dxb), ['first_name' => 'Ben', 'last_name' => 'Reyes', 'nickname' => 'Ben']))->create();

        actingAs($admin)->get('/ccf/card-requests?tab=asked&q=amara+reyes')->assertInertia(fn (AssertableInertia $p) => $p
            ->where('q', 'amara reyes')
            ->where('pagination.total', 1)
            ->where('requests.0.id', $hers->id));
        actingAs($admin)->get('/ccf/card-requests?tab=approved&q=amara')->assertInertia(fn (AssertableInertia $p) => $p->where('pagination.total', 0));
    });
});

describe('moving a request', function (): void {
    it('has nothing to pay on a free first card — and says so @story#137', function (): void {
        ['amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create(['reason' => CardReasonEnum::FirstCard, 'fee_minor' => 0]);

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/mark-paid")->assertSessionHas('error', "Amara's first card is free — nothing to pay.");

        expect($request->fresh()?->paid_at)->toBeNull();
    });

    it('refuses an approved card too, before it is printed @story#140', function (): void {
        ['amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create(['step' => CardStepEnum::Approved, 'paid_at' => now()]);

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/refuse", ['reason' => 'Parent found it'])->assertSessionHas('success', "Amara's card is refused.");

        expect([$request->fresh()?->step, $request->fresh()?->refused_reason])->toBe([CardStepEnum::Refused, 'Parent found it']);
    });

    it('marks the AED 10 paid @story#136', function (): void {
        ['amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create();

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/mark-paid")->assertSessionHas('success', "Amara's card is marked paid.");

        expect($request->fresh()?->paid_by)->toBe($admin->id);
    });

    it('says where a request is when the move finds it moved on — paid already @story#137', function (): void {
        ['amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create(['paid_at' => now(), 'step' => CardStepEnum::Approved]);

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/mark-paid")->assertSessionHas('error', "Amara's card has already moved on: Approved · paid.");
    });

    it('approves a paid or free request for printing @story#138', function (array $state): void {
        /** @var array<model-property<CardRequest>, mixed> $state */
        ['amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create($state);

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/approve")->assertSessionHas('success', "Amara's card is approved for printing.");

        expect($request->fresh()?->step)->toBe(CardStepEnum::Approved);
    })->with([
        'paid' => [['paid_at' => '2026-10-30 10:00:00']],
        'free' => [['reason' => CardReasonEnum::FirstCard, 'fee_minor' => 0]],
    ]);

    it('approves nothing unpaid — it says to mark it paid first — and nothing twice @story#139', function (): void {
        ['amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create();

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/approve")->assertSessionHas('error', "Amara's card is not paid yet: mark the AED 10 paid first.");
        expect($request->fresh()?->step)->toBe(CardStepEnum::Asked);

        $request->forceFill(['paid_at' => now(), 'step' => CardStepEnum::Approved])->save();
        actingAs($admin)->put("/ccf/card-requests/{$request->id}/approve")->assertSessionHas('error', "Amara's card has already moved on: Approved · paid.");
    });

    it('refuses with a reason, freeing the child for a new request @story#140', function (): void {
        ['dxb' => $dxb, 'amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create();

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/refuse", ['reason' => '  Found it at home  '])->assertSessionHas('success', "Amara's card is refused.");
        expect([$request->fresh()?->step, $request->fresh()?->refused_reason])->toBe([CardStepEnum::Refused, 'Found it at home']);

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->post("/ccf/children/{$amara->id}/card-requests", ['reason' => 'damaged'])->assertSessionHas('success');
        expect(CardRequest::query()->where('child_id', $amara->id)->count())->toBe(2);
    });

    it('refuses nothing without a reason, or once printed @story#141', function (): void {
        ['amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create();

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/refuse", ['reason' => '   '])->assertSessionHasErrors(['reason' => 'Please say why, in a few words.']);
        $request->forceFill(['step' => CardStepEnum::Printed])->save();
        actingAs($admin)->put("/ccf/card-requests/{$request->id}/refuse", ['reason' => 'Late'])->assertSessionHas('error', "Amara's card has already moved on: Printed · not paid.");

        expect($request->fresh()?->step)->toBe(CardStepEnum::Printed);
    });

    it('marks one approved card printed @story#142', function (): void {
        ['amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create(['step' => CardStepEnum::Approved, 'paid_at' => now()]);

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/mark-printed")->assertSessionHas('success', "Amara's card is marked printed.");

        expect($request->fresh()?->step)->toBe(CardStepEnum::Printed);
    });

    it('prints nothing not approved @story#143', function (): void {
        ['amara' => $amara, 'admin' => $admin] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create();

        actingAs($admin)->put("/ccf/card-requests/{$request->id}/mark-printed")->assertSessionHas('error', "Amara's card has already moved on: Asked · not paid.");
    });

    it('marks every approved card the admin sees printed, saying how many @story#144', function (): void {
        ['dxb' => $dxb, 'amara' => $amara, 'admin' => $admin] = cardsChurch();
        CardRequest::factory()->for($amara)->create(['step' => CardStepEnum::Approved]);
        CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]);

        actingAs($admin)->put('/ccf/card-requests/mark-all-printed')->assertSessionHas('success', 'Marked 2 cards printed.');
        actingAs($admin)->put('/ccf/card-requests/mark-all-printed')->assertSessionHas('success', 'There were no approved cards to mark printed.');
    });

    it('never marks another satellite\'s approved cards printed @story#145', function (): void {
        ['house' => $house] = cardsChurch();
        $theirs = CardRequest::factory()->for(childOf(familyAt($house)))->create(['step' => CardStepEnum::Approved]);
        $dxbAdmin = staffMember(RoleEnum::Admin, atSatellite(Satellite::query()->where('name', 'CCF DXB')->sole()));

        actingAs($dxbAdmin)->put('/ccf/card-requests/mark-all-printed')->assertSessionHas('success', 'There were no approved cards to mark printed.');

        expect($theirs->fresh()?->step)->toBe(CardStepEnum::Approved);
    });
});

describe('giving a printed card at the desk', function (): void {
    it('lets a Lifeshaper mark it given from the family page; the child may then have a new one @story#146', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create(['step' => CardStepEnum::Printed]);
        $joy = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));

        actingAs($joy)->put("/ccf/card-requests/{$request->id}/give")->assertSessionHas('success', "Amara's new card is given.");

        expect([$request->fresh()?->step, $request->fresh()?->given_by])->toBe([CardStepEnum::Given, $joy->id]);
        actingAs($joy)->get("/ccf/families/{$amara->family_id}")->assertInertia(fn (AssertableInertia $p) => $p->where('card_requests', []));
    });

    it('gives nothing not yet printed, nor twice; a parent cannot @story#147', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = cardsChurch();
        $request = CardRequest::factory()->for($amara)->create(['step' => CardStepEnum::Approved]);
        $parent = User::factory()->create();
        guardianOf($amara->family, $parent);

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->put("/ccf/card-requests/{$request->id}/give")
            ->assertSessionHas('error', "Amara's card has already moved on: Approved · not paid.");
        actingAs($parent)->put("/ccf/card-requests/{$request->id}/give")->assertNotFound();

        expect($request->fresh()?->step)->toBe(CardStepEnum::Approved);
    });
});
