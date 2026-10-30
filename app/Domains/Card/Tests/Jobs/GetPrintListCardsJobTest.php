<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\PrintList;
use App\Domains\Card\Jobs\GetPrintListCardsJob;
use App\Enums\CardStepEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A Print list's cards as the viewer may see them: its members only, of the viewer's satellites, in the order filed. */

it('gives the list\'s own cards the viewer may see — whatever step they are at now — with their child', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $a = CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]);
    $b = CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Printed]);
    $theirs = CardRequest::factory()->for(childOf(familyAt($house)))->create(['step' => CardStepEnum::Approved]);
    CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]);
    $list = PrintList::factory()->for($church)->create();
    $list->cards()->attach([$a->id, $b->id, $theirs->id]);

    $dxbCards = (new GetPrintListCardsJob(church: $church, printList: $list, satelliteIds: [$dxb->id]))->handle();
    $all = (new GetPrintListCardsJob(church: $church, printList: $list, satelliteIds: null))->handle();

    expect(array_map(fn (CardRequest $r): int => $r->id, $dxbCards))->toBe([$a->id, $b->id])
        ->and($dxbCards[0]->relationLoaded('child'))->toBeTrue()
        ->and(array_map(fn (CardRequest $r): int => $r->id, $all))->toBe([$a->id, $b->id, $theirs->id]);
});

it('leaves out a card of another church, should one ever be on the list', function (): void {
    $church = church();
    $mine = CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create(['step' => CardStepEnum::Approved]);
    $foreign = CardRequest::factory()->for(childOf(familyAt(satelliteOf(church()))))->create(['step' => CardStepEnum::Approved]);
    $list = PrintList::factory()->for($church)->create();
    $list->cards()->attach([$mine->id, $foreign->id]);

    expect(array_map(fn (CardRequest $r): int => $r->id, (new GetPrintListCardsJob(church: $church, printList: $list, satelliteIds: null))->handle()))->toBe([$mine->id]);
});
