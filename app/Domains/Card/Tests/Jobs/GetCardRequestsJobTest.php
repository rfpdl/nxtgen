<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Domains\Card\Jobs\GetCardRequestsJob;
use App\Enums\CardListTabEnum;
use App\Enums\CardStepEnum;
use Illuminate\Support\Facades\DB;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * The Cards page's list: one tab's requests, of the satellites the admin sees (null = all), in this church only —
 * oldest first while there is something to do, the newest filed first once closed. Paged in the database (ListPage).
 */

it('lists one tab\'s requests of the given satellites, oldest first — never another satellite\'s or church\'s', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $first = CardRequest::factory()->for(childOf(familyAt($dxb)))->create();
    $second = CardRequest::factory()->for(childOf(familyAt($dxb)))->create();
    CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]);
    CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create();
    CardRequest::factory()->for(childOf(familyAt(satelliteOf(church()))))->create();

    $page = (new GetCardRequestsJob(church: $church, satelliteIds: [$dxb->id], tab: CardListTabEnum::Asked, page: 1))->handle();

    expect(array_map(fn (CardRequest $r): int => $r->id, $page->items))->toBe([$first->id, $second->id])
        ->and($page->total)->toBe(2);
});

it('lists every satellite\'s for a church admin, and the closed tab newest filed first — Given and Refused', function (): void {
    $church = church();
    $given = CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create(['step' => CardStepEnum::Given]);
    $refused = CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create(['step' => CardStepEnum::Refused]);
    CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create(['step' => CardStepEnum::Printed]);

    $page = (new GetCardRequestsJob(church: $church, satelliteIds: null, tab: CardListTabEnum::Closed, page: 1))->handle();

    expect(array_map(fn (CardRequest $r): int => $r->id, $page->items))->toBe([$refused->id, $given->id]);
});

it('pages in the database, and asks the same number of times however many rows a page holds', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $count = function () use ($church): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $page = (new GetCardRequestsJob(church: $church, satelliteIds: null, tab: CardListTabEnum::Asked, page: 1))->handle();
        // What a row shows (CardRequestRowData): the child, the family's parent and its satellite.
        $names = array_map(fn (CardRequest $r): string => $r->child->fullName().$r->child->family->contactName().$r->child->family->homeSatellite->name, $page->items);
        expect($names)->toHaveCount(count($page->items));
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };
    CardRequest::factory()->for(childOf(familyAt($dxb)))->create();
    $few = $count();
    CardRequest::factory()->count(3)->sequence(fn () => ['child_id' => childOf(familyAt($dxb))->id])->create();

    expect($count())->toBe($few)
        ->and((new GetCardRequestsJob(church: $church, satelliteIds: null, tab: CardListTabEnum::Asked, page: 2, perPage: 3))->handle()->items)->toHaveCount(1);
});
