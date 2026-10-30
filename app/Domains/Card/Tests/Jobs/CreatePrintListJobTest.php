<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\PrintList;
use App\Data\Models\User;
use App\Domains\Card\Jobs\CreatePrintListJob;
use App\Enums\CardStepEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* Making a Print list: exactly the Approved cards the tab shows — the admin's satellites, the search — kept by id. */

it('keeps exactly the approved cards of the admin\'s satellites and search, saying who made it', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $jon = CardRequest::factory()->for(childOf(familyAt($dxb), ['first_name' => 'Jon', 'last_name' => 'Ocampo']))->create(['step' => CardStepEnum::Approved]);
    $jonToo = CardRequest::factory()->for(childOf(familyAt($dxb), ['first_name' => 'Jon', 'last_name' => 'Ocampo']))->create(['step' => CardStepEnum::Approved]);
    CardRequest::factory()->for(childOf(familyAt($dxb), ['first_name' => 'Kit', 'last_name' => 'Ocampo']))->create(['step' => CardStepEnum::Approved]);
    CardRequest::factory()->for(childOf(familyAt($dxb), ['first_name' => 'Jon', 'last_name' => 'Asked']))->create();
    CardRequest::factory()->for(childOf(familyAt(satelliteOf($church)), ['first_name' => 'Jon', 'last_name' => 'Elsewhere']))->create(['step' => CardStepEnum::Approved]);
    $grace = User::factory()->create();

    $list = (new CreatePrintListJob(church: $church, satelliteIds: [$dxb->id], query: 'jon', by: $grace))->handle();

    expect($list)->toBeInstanceOf(PrintList::class)
        ->and([$list?->church_id, $list?->made_by])->toBe([$church->id, $grace->id])
        ->and($list?->cards()->orderBy('card_requests.id')->pluck('card_requests.id')->all())->toBe([$jon->id, $jonToo->id])
        ->and($list?->cards_count)->toBe(2);
});

it('makes no list when nothing approved is shown', function (): void {
    $church = church();
    CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create();

    expect((new CreatePrintListJob(church: $church, satelliteIds: null, query: null, by: User::factory()->create()))->handle())->toBeNull()
        ->and(PrintList::query()->count())->toBe(0);
});
