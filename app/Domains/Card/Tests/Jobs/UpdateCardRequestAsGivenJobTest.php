<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Domains\Card\Jobs\UpdateCardRequestAsGivenJob;
use App\Enums\CardStepEnum;

use function Tests\Support\atTheSameTime;
use function Tests\Support\withCommittedWorld;

/* Giving a printed card to the child or parent, at the desk: only a Printed one. Given closes it, freeing the Child. */

it('marks a Printed request given, saying who and when — and frees the child', function (): void {
    $request = CardRequest::factory()->create(['step' => CardStepEnum::Printed]);
    $joy = User::factory()->create();

    expect((new UpdateCardRequestAsGivenJob(request: $request, by: $joy))->handle())->toBeTrue();

    $fresh = $request->fresh();
    expect([$fresh?->step, $fresh?->given_by, $fresh?->given_at !== null, $fresh?->open_child_id])->toBe([CardStepEnum::Given, $joy->id, true, null]);
});

it('gives nothing not yet printed, or already closed', function (CardStepEnum $step): void {
    $request = CardRequest::factory()->create(['step' => $step]);

    expect((new UpdateCardRequestAsGivenJob(request: $request, by: User::factory()->create()))->handle())->toBeFalse()
        ->and($request->fresh()?->step)->toBe($step);
})->with([CardStepEnum::Asked, CardStepEnum::Approved, CardStepEnum::Given, CardStepEnum::Refused]);

it('gives ONCE when the desk and Cards mark the same card given at the same moment', function (): void {
    $request = CardRequest::factory()->create(['step' => CardStepEnum::Printed]);
    $desk = User::factory()->create();
    $office = User::factory()->create();

    withCommittedWorld(function () use ($request, $desk, $office): void {
        $results = atTheSameTime(
            fn (): bool => (new UpdateCardRequestAsGivenJob(request: $request, by: $desk))->handle(),
            fn (): bool => (new UpdateCardRequestAsGivenJob(request: $request, by: $office))->handle(),
        );

        $fresh = CardRequest::query()->sole();
        expect($results)->toEqualCanonicalizing([true, false])
            ->and($fresh->step)->toBe(CardStepEnum::Given)
            ->and($fresh->given_by)->toBe($results[0] ? $desk->id : $office->id);
    });
});
