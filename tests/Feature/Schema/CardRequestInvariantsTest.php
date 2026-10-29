<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Enums\CardStepEnum;
use Illuminate\Database\QueryException;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * One OPEN Card request per Child — held by the database. `open_child_id` is worked out by MySQL from the step (the
 * child while open, else null) and is unique. Its CASE names the open steps itself (a migration never changes once
 * run), so this pins it to CardStepEnum: a step added to the enum, or a migration that drifts, fails here.
 */

it('holds the child in open_child_id exactly while the step is open — for every step', function (CardStepEnum $step): void {
    $child = childOf(familyAt(satelliteOf(church())));

    $request = CardRequest::factory()->for($child)->create(['step' => $step]);

    expect($request->fresh()?->open_child_id)->toBe($step->isOpen() ? $child->id : null);
})->with(CardStepEnum::cases());

it('frees the child when a request closes, and holds them again when it is reopened', function (): void {
    $child = childOf(familyAt(satelliteOf(church())));
    $request = CardRequest::factory()->for($child)->create();

    $request->forceFill(['step' => CardStepEnum::Given])->save();
    expect($request->fresh()?->open_child_id)->toBeNull();

    $request->forceFill(['step' => CardStepEnum::Printed])->save();
    expect($request->fresh()?->open_child_id)->toBe($child->id);
});

it('refuses a second open request for the same child, whatever step each is at', function (): void {
    $child = childOf(familyAt(satelliteOf(church())));
    CardRequest::factory()->for($child)->create(['step' => CardStepEnum::Printed]);

    expect(fn () => CardRequest::factory()->for($child)->create(['step' => CardStepEnum::Asked]))
        ->toThrow(QueryException::class, 'card_requests_open_child_id_unique');
});

it('keeps any number of closed requests for a child', function (): void {
    $child = childOf(familyAt(satelliteOf(church())));
    CardRequest::factory()->for($child)->count(2)->create(['step' => CardStepEnum::Given]);
    CardRequest::factory()->for($child)->create(['step' => CardStepEnum::Refused]);

    expect(CardRequest::query()->where('child_id', $child->id)->count())->toBe(3);
});
