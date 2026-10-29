<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Domains\Card\Jobs\GetFamilyOpenCardRequestsJob;
use App\Enums\CardStepEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A family's children's OPEN Card requests, for the staff family page: never a closed one, never another family's. */

it('gives the family\'s open requests in the order filed (by id) — not a Given or Refused one, not another family\'s', function (): void {
    $dxb = satelliteOf(church());
    $family = familyAt($dxb);
    $amara = childOf($family, ['nickname' => 'Amara']);
    $ben = childOf($family, ['nickname' => 'Ben']);
    $asked = CardRequest::factory()->for($amara)->create();
    CardRequest::factory()->for($ben)->create(['step' => CardStepEnum::Given]);
    CardRequest::factory()->for($ben)->create(['step' => CardStepEnum::Refused]);
    // Filed later, though its created_at says earlier (a backfill, a clock): the order is the filing order, by id.
    $printed = CardRequest::factory()->for($ben)->create(['step' => CardStepEnum::Printed, 'created_at' => now()->subWeek()]);
    CardRequest::factory()->for(childOf(familyAt($dxb)))->create();

    $open = (new GetFamilyOpenCardRequestsJob(family: $family))->handle();

    expect(array_map(fn (CardRequest $r): int => $r->id, $open))->toBe([$asked->id, $printed->id]);
});

it('leaves out a request carrying another church, even for this family\'s child', function (): void {
    $family = familyAt(satelliteOf(church()));
    $amara = childOf($family);
    CardRequest::factory()->for($amara)->create(['church_id' => church()->id]);

    expect((new GetFamilyOpenCardRequestsJob(family: $family))->handle())->toBe([]);
});
