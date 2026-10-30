<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\PrintList;
use App\Data\Models\Satellite;
use App\Domains\Card\Jobs\GetOpenPrintListsJob;
use App\Enums\CardStepEnum;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* The Print lists still to print, for the Approved tab: newest first, counted as the viewer sees them. */

it('lists the lists with a card the viewer may still mark printed, newest first, with how many it holds and has left', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $approved = fn (Satellite $sat): CardRequest => CardRequest::factory()->for(childOf(familyAt($sat)))->create(['step' => CardStepEnum::Approved]);
    travelTo(CarbonImmutable::parse('2026-10-30 09:00:00'));
    $older = PrintList::factory()->for($church)->create();
    $older->cards()->attach([$approved($dxb)->id, CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Printed])->id]);
    travelTo(CarbonImmutable::parse('2026-10-31 09:00:00'));
    $newer = PrintList::factory()->for($church)->create();
    $newer->cards()->attach([$approved($dxb)->id, $approved($house)->id]);
    $done = PrintList::factory()->for($church)->create();
    $done->cards()->attach([CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Printed])->id]);
    $houseOnly = PrintList::factory()->for($church)->create();
    $houseOnly->cards()->attach([$approved($house)->id]);
    $otherChurch = church();
    $theirs = PrintList::factory()->for($otherChurch)->create();
    $theirs->cards()->attach([$approved(satelliteOf($otherChurch))->id]);

    $lists = (new GetOpenPrintListsJob(church: $church, satelliteIds: [$dxb->id]))->handle();

    expect(array_map(fn (PrintList $l): array => [$l->id, $l->cards_count, $l->to_print_count], $lists))
        ->toBe([[$newer->id, 1, 1], [$older->id, 2, 1]]);

    // A church admin (every satellite): this church's open lists only — never the other church's.
    $all = array_map(fn (PrintList $l): int => $l->id, (new GetOpenPrintListsJob(church: $church, satelliteIds: null))->handle());
    expect($all)->toEqualCanonicalizing([$newer->id, $older->id, $houseOnly->id])
        ->and($all)->not->toContain($theirs->id);
});
