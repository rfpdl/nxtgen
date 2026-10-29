<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Domains\Card\Jobs\CreateFirstCardRequestJob;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use App\Enums\RegistrationSourceEnum;
use Illuminate\Database\QueryException;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A newly registered Child's free first name card (name cards 02): asked for by the app — never for an imported one. */

it('asks for a free first card for a child a parent or staff registered, made by the app', function (RegistrationSourceEnum $source): void {
    $amara = childOf(familyAt(satelliteOf(church())));

    $request = (new CreateFirstCardRequestJob(child: $amara, source: $source))->handle();

    expect($request?->is(CardRequest::query()->sole()))->toBeTrue()
        ->and([$request?->church_id, $request?->child_id, $request?->reason, $request?->step, $request?->fee_minor, $request?->note, $request?->created_by])
        ->toBe([$amara->church_id, $amara->id, CardReasonEnum::FirstCard, CardStepEnum::Asked, 0, null, null]);
})->with([RegistrationSourceEnum::Self, RegistrationSourceEnum::Staff]);

it('asks for none for an imported child — they already have a card', function (): void {
    $amara = childOf(familyAt(satelliteOf(church())));

    expect((new CreateFirstCardRequestJob(child: $amara, source: RegistrationSourceEnum::Import))->handle())->toBeNull()
        ->and(CardRequest::query()->count())->toBe(0);
});

it('fails loudly rather than quietly skip, should the child somehow have an open request already', function (): void {
    $amara = childOf(familyAt(satelliteOf(church())));
    CardRequest::factory()->for($amara)->create();

    expect(fn () => (new CreateFirstCardRequestJob(child: $amara, source: RegistrationSourceEnum::Self))->handle())
        ->toThrow(QueryException::class, 'card_requests_open_child_id_unique');
});
