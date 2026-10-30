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
 * Name cards, issue 01 (DECISIONS 2026-10-29; GLOSSARY "Card request"): on the staff family page, staff who may open
 * the family file a new card for a child — Lost or Damaged, with an optional note — and the child's card then shows
 * where it is ("Card: Asked · not paid"). One open request per Child. Parents file nothing and see nothing yet. Every
 * route's who-may grid is AccessMatrixTest's; this file is the rules.
 */

/** @return array{church: Church, dxb: Satellite, house: Satellite, amara: Child} */
function cardChurch(): array
{
    $church = church(['slug' => 'ccf']);
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    $amara = childOf(familyAt($dxb), ['nickname' => 'Amara']);

    return ['church' => $church, 'dxb' => $dxb, 'house' => $house, 'amara' => $amara];
}

describe('filing a new card', function (): void {
    it('lets a Lifeshaper file a lost card with a note — Asked, AED 10, not paid, by them @story#130', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = cardChurch();
        $joy = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));

        actingAs($joy)->post("/ccf/children/{$amara->id}/card-requests", ['reason' => 'lost', 'note' => 'Lost at the park'])
            ->assertRedirect()->assertSessionHas('success', 'A new card for Amara is asked for.');

        $request = CardRequest::query()->sole();
        expect([$request->child_id, $request->reason, $request->note, $request->step, $request->fee_minor, $request->paid_at, $request->created_by])
            ->toBe([$amara->id, CardReasonEnum::Lost, 'Lost at the park', CardStepEnum::Asked, 1000, null, $joy->id]);
    });

    it('lets an admin file a damaged card with no note @story#130', function (): void {
        ['church' => $church, 'amara' => $amara] = cardChurch();

        actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->post("/ccf/children/{$amara->id}/card-requests", ['reason' => 'damaged', 'note' => null])
            ->assertSessionHas('success');

        expect([CardRequest::query()->sole()->reason, CardRequest::query()->sole()->note])->toBe([CardReasonEnum::Damaged, null]);
    });

    it('keeps a note of exactly 200 letters, and stores a blank note as none @story#130', function (string $note, ?string $stored): void {
        ['dxb' => $dxb, 'amara' => $amara] = cardChurch();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->post("/ccf/children/{$amara->id}/card-requests", ['reason' => 'lost', 'note' => $note])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        expect(CardRequest::query()->sole()->note)->toBe($stored);
    })->with([
        '200 letters' => [str_repeat('a', 200), str_repeat('a', 200)],
        'only spaces' => ['   ', null],
        'padded' => ['  Torn  ', 'Torn'],
    ]);

    it('files a new request once the last one is closed — Given or Refused — keeping the old one @story#130', function (CardStepEnum $step): void {
        ['dxb' => $dxb, 'amara' => $amara] = cardChurch();
        $closed = CardRequest::factory()->for($amara)->create(['step' => $step]);

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->post("/ccf/children/{$amara->id}/card-requests", ['reason' => 'damaged'])
            ->assertSessionHas('success', 'A new card for Amara is asked for.');

        $requests = CardRequest::query()->where('child_id', $amara->id)->orderBy('id')->get();
        expect($requests->pluck('id')->first())->toBe($closed->id)
            ->and($requests->map(fn (CardRequest $r): CardStepEnum => $r->step)->all())->toBe([$step, CardStepEnum::Asked]);
    })->with([CardStepEnum::Given, CardStepEnum::Refused]);

    it('files nothing for a family the staff member cannot open — another satellite\'s Lifeshaper gets a 404 @story#131', function (): void {
        ['house' => $house, 'amara' => $amara] = cardChurch();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($house)))->post("/ccf/children/{$amara->id}/card-requests", ['reason' => 'lost'])
            ->assertNotFound();

        expect(CardRequest::query()->count())->toBe(0);
    });

    it('files nothing for a parent, even their own child — they tell a Lifeshaper @story#131', function (): void {
        ['amara' => $amara] = cardChurch();
        $parent = User::factory()->create();
        guardianOf($amara->family, $parent);

        actingAs($parent)->post("/ccf/children/{$amara->id}/card-requests", ['reason' => 'lost'])->assertNotFound();

        expect(CardRequest::query()->count())->toBe(0);
    });

    it('refuses a first card, a reason it does not know, or a note longer than 200 letters @story#131', function (array $payload, string $field): void {
        ['dxb' => $dxb, 'amara' => $amara] = cardChurch();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->post("/ccf/children/{$amara->id}/card-requests", $payload)
            ->assertSessionHasErrors($field);

        expect(CardRequest::query()->count())->toBe(0);
    })->with([
        'first card' => [['reason' => 'first_card'], 'reason'],
        'unknown' => [['reason' => 'stolen'], 'reason'],
        'none' => [[], 'reason'],
        'long note' => [['reason' => 'lost', 'note' => str_repeat('a', 201)], 'note'],
    ]);

    it('files no second request while one is open — it says which one is open instead @story#131', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = cardChurch();
        $open = CardRequest::factory()->for($amara)->create(['step' => CardStepEnum::Approved, 'reason' => CardReasonEnum::Lost]);

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->post("/ccf/children/{$amara->id}/card-requests", ['reason' => 'damaged'])
            ->assertRedirect()->assertSessionHas('error', 'Amara already has a new card on the way: Approved · not paid.');

        expect(CardRequest::query()->sole()->is($open))->toBeTrue();
    });
});

describe('the family page', function (): void {
    it('shows each child\'s open Card request — its step and whether it is paid — and nothing for a child without one @story#132', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = cardChurch();
        $ben = childOf($amara->family, ['nickname' => 'Ben']);
        $cy = childOf($amara->family, ['nickname' => 'Cy']);
        $asked = CardRequest::factory()->for($amara)->create();
        CardRequest::factory()->for($ben)->create(['step' => CardStepEnum::Given]);
        $paid = CardRequest::factory()->for($ben)->create(['step' => CardStepEnum::Printed, 'paid_at' => now()]);
        $free = CardRequest::factory()->for($cy)->create(['reason' => CardReasonEnum::FirstCard, 'fee_minor' => 0]);

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get("/ccf/families/{$amara->family_id}")
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->where('card_requests', [
                    ['id' => $asked->id, 'child_id' => $amara->id, 'step' => 'asked', 'fee' => 'not_paid', 'summary' => 'Asked · not paid', 'moves' => []],
                    ['id' => $paid->id, 'child_id' => $ben->id, 'step' => 'printed', 'fee' => 'paid', 'summary' => 'Printed · paid', 'moves' => ['give']],
                    ['id' => $free->id, 'child_id' => $cy->id, 'step' => 'asked', 'fee' => 'free', 'summary' => 'Asked · free', 'moves' => []],
                ])
                ->where('enums.card_reason', [
                    ['value' => 'lost', 'label' => 'Lost', 'color' => 'muted'],
                    ['value' => 'damaged', 'label' => 'Damaged', 'color' => 'muted'],
                ]));
    });

    it('shows a child with no open request nothing — a closed one is history @story#132', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = cardChurch();
        CardRequest::factory()->for($amara)->create(['step' => CardStepEnum::Refused]);

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get("/ccf/families/{$amara->family_id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('card_requests', []));
    });
});
