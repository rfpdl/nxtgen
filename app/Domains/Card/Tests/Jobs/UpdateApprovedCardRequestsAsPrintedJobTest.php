<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Domains\Card\Jobs\UpdateApprovedCardRequestsAsPrintedJob;
use App\Enums\CardStepEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* "Mark all printed": every Approved request the admin sees — their satellites' families, this church — and no other. */

it('marks every Approved request of the given satellites printed, and says how many', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $here = [
        CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]),
        CardRequest::factory()->for(childOf(familyAt($dxb)))->create(['step' => CardStepEnum::Approved]),
    ];
    $asked = CardRequest::factory()->for(childOf(familyAt($dxb)))->create();
    $there = CardRequest::factory()->for(childOf(familyAt($house)))->create(['step' => CardStepEnum::Approved]);
    $elsewhere = CardRequest::factory()->for(childOf(familyAt(satelliteOf(church()))))->create(['step' => CardStepEnum::Approved]);
    $joy = User::factory()->create();

    $count = (new UpdateApprovedCardRequestsAsPrintedJob(church: $church, satelliteIds: [$dxb->id], by: $joy))->handle();

    expect($count)->toBe(2)
        ->and(array_map(fn (CardRequest $r): array => [$r->fresh()?->step, $r->fresh()?->printed_by], $here))->toBe([[CardStepEnum::Printed, $joy->id], [CardStepEnum::Printed, $joy->id]])
        ->and([$asked->fresh()?->step, $there->fresh()?->step, $elsewhere->fresh()?->step])->toBe([CardStepEnum::Asked, CardStepEnum::Approved, CardStepEnum::Approved]);
});

it('marks every satellite\'s Approved requests of this church for a church admin (no satellites given)', function (): void {
    $church = church();
    CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create(['step' => CardStepEnum::Approved]);
    CardRequest::factory()->for(childOf(familyAt(satelliteOf($church))))->create(['step' => CardStepEnum::Approved]);
    $other = CardRequest::factory()->for(childOf(familyAt(satelliteOf(church()))))->create(['step' => CardStepEnum::Approved]);

    expect((new UpdateApprovedCardRequestsAsPrintedJob(church: $church, satelliteIds: null, by: User::factory()->create()))->handle())->toBe(2)
        ->and($other->fresh()?->step)->toBe(CardStepEnum::Approved);
});
