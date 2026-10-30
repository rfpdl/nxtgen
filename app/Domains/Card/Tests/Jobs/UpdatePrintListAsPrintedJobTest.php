<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\PrintList;
use App\Data\Models\User;
use App\Domains\Card\Jobs\UpdateCardRequestAsRefusedJob;
use App\Domains\Card\Jobs\UpdatePrintListAsPrintedJob;
use App\Enums\CardStepEnum;

use function Tests\Support\atTheSameTime;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\withCommittedWorld;

/*
 * Marking a Print list printed: its own cards only — those still Approved and within the admin's satellites. A card
 * that was never on it stays, whatever its name, family or approval time; one that moved on is skipped.
 */

it('marks the list\'s still-approved cards printed, saying who and when, and how many', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $a = CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]);
    $b = CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]);
    $given = CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Given]);
    $list = PrintList::factory()->for($church)->create();
    $list->cards()->attach([$a->id, $b->id, $given->id]);
    $grace = User::factory()->create();

    expect((new UpdatePrintListAsPrintedJob(church: $church, printList: $list, satelliteIds: null, by: $grace))->handle())->toBe(2)
        ->and([$a->fresh()?->step, $a->fresh()?->printed_by, $b->fresh()?->step, $given->fresh()?->step])
        ->toBe([CardStepEnum::Printed, $grace->id, CardStepEnum::Printed, CardStepEnum::Given]);
});

it('never marks a card that was not on the list — approved later, renamed or moved into the admin\'s satellites after', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $on = CardRequest::factory()->for(childOf(familyAt($dxb), ['first_name' => 'Jon']))->create(['step' => CardStepEnum::Approved]);
    $list = PrintList::factory()->for($church)->create();
    $list->cards()->attach([$on->id]);
    $approvedLater = CardRequest::factory()->for(childOf(familyAt($dxb), ['first_name' => 'Jon']))->create(['step' => CardStepEnum::Approved]);
    $movedIn = CardRequest::factory()->for(childOf($family = familyAt($house), ['first_name' => 'Jon']))->create(['step' => CardStepEnum::Approved]);
    $family->forceFill(['home_satellite_id' => $dxb->id])->save();

    expect((new UpdatePrintListAsPrintedJob(church: $church, printList: $list, satelliteIds: [$dxb->id], by: User::factory()->create()))->handle())->toBe(1)
        ->and([$on->fresh()?->step, $approvedLater->fresh()?->step, $movedIn->fresh()?->step])
        ->toBe([CardStepEnum::Printed, CardStepEnum::Approved, CardStepEnum::Approved]);
});

it('marks only the cards within the admin\'s satellites — another satellite\'s card on the list is left', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $mine = CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]);
    $theirs = CardRequest::factory()->for(childOf(familyAt($house)))->create(['step' => CardStepEnum::Approved]);
    $list = PrintList::factory()->for($church)->create();
    $list->cards()->attach([$mine->id, $theirs->id]);

    expect((new UpdatePrintListAsPrintedJob(church: $church, printList: $list, satelliteIds: [$dxb->id], by: User::factory()->create()))->handle())->toBe(1)
        ->and([$mine->fresh()?->step, $theirs->fresh()?->step])->toBe([CardStepEnum::Printed, CardStepEnum::Approved]);
});

it('never both prints and refuses a card refused at the same moment as its list is marked printed', function (): void {
    $church = church();
    $card = CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create(['step' => CardStepEnum::Approved]);
    $list = PrintList::factory()->for($church)->create();
    $list->cards()->attach([$card->id]);
    $grace = User::factory()->create();

    withCommittedWorld(function () use ($church, $list, $card, $grace): void {
        [$printed, $refused] = atTheSameTime(
            fn (): int => (new UpdatePrintListAsPrintedJob(church: $church, printList: $list, satelliteIds: null, by: $grace))->handle(),
            fn (): bool => (new UpdateCardRequestAsRefusedJob(request: $card, reason: 'Found it', by: $grace))->handle(),
        );

        $step = CardRequest::query()->sole()->step;
        expect([$printed, $refused])->toBeIn([[1, false], [0, true]])
            ->and($step)->toBe($printed === 1 ? CardStepEnum::Printed : CardStepEnum::Refused);
    });
});

it('skips a card of another church, should one ever be on the list — the acting church is a second guard', function (): void {
    $church = church();
    $mine = CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create(['step' => CardStepEnum::Approved]);
    $foreign = CardRequest::factory()->for(childOf(familyAt(satelliteOf(church()))))->create(['step' => CardStepEnum::Approved]);
    $list = PrintList::factory()->for($church)->create();
    $list->cards()->attach([$mine->id, $foreign->id]);

    expect((new UpdatePrintListAsPrintedJob(church: $church, printList: $list, satelliteIds: null, by: User::factory()->create()))->handle())->toBe(1)
        ->and($foreign->fresh()?->step)->toBe(CardStepEnum::Approved);
});
