<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Domains\Card\DataObjects\CardRequestRowData;
use App\Enums\CardMoveEnum;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A row of the Cards page: whose card, why, and where the fee stands — in the words an admin reads. */

it('says the fee as an admin reads it — AED 10 not paid or paid, or free', function (array $state, string $label): void {
    /** @var array<model-property<CardRequest>, mixed> $state */
    $request = CardRequest::factory()->for(childOf(familyAt(satelliteOf(church()))))->create($state);

    expect(CardRequestRowData::fromRequest($request)->fee_label)->toBe($label);
})->with([
    'not paid' => [[], 'AED 10 · not paid'],
    'paid' => [['paid_at' => '2026-10-30 10:00:00'], 'AED 10 · paid'],
    'first card' => [['reason' => CardReasonEnum::FirstCard, 'fee_minor' => 0], 'Free'],
]);

it('names the child in full, the family, satellite, reason, note and a refusal\'s reason', function (): void {
    $satellite = satelliteOf(church(), ['name' => 'CCF DXB']);
    $amara = childOf(familyAt($satellite), ['first_name' => 'Amara', 'middle_name' => 'Joy', 'last_name' => 'Reyes']);
    $request = CardRequest::factory()->for($amara)->create(['note' => 'Lost at the park', 'step' => CardStepEnum::Refused, 'refused_reason' => 'Found it']);

    $row = CardRequestRowData::fromRequest($request);

    expect([$row->child_name, $row->family_id, $row->satellite, $row->reason_label, $row->note, $row->step_label, $row->refused_reason])
        ->toBe(['Amara Joy Reyes', $amara->family_id, 'CCF DXB', 'Lost', 'Lost at the park', 'Refused', 'Found it']);
});

it('says the fee to the fils, from the request\'s own amount', function (): void {
    $request = CardRequest::factory()->for(childOf(familyAt(satelliteOf(church()))))->create(['fee_minor' => 1050]);

    expect(CardRequestRowData::fromRequest($request)->fee_label)->toBe('AED 10.50 · not paid');
});

it('sends the moves the server allows now — the screen works none out', function (): void {
    $request = CardRequest::factory()->for(childOf(familyAt(satelliteOf(church()))))->create(['step' => CardStepEnum::Approved, 'paid_at' => now()]);

    expect(CardRequestRowData::fromRequest($request)->moves)->toBe([CardMoveEnum::Refuse, CardMoveEnum::MarkPrinted]);
});
