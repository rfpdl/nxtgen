<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Domains\Card\Jobs\UpdateCardRequestAsPrintedJob;
use App\Enums\CardStepEnum;

/* Marking one Card request printed: only an Approved one. */

it('marks an Approved request printed, saying who and when', function (): void {
    $request = CardRequest::factory()->create(['step' => CardStepEnum::Approved]);
    $joy = User::factory()->create();

    expect((new UpdateCardRequestAsPrintedJob(request: $request, by: $joy))->handle())->toBeTrue();

    $fresh = $request->fresh();
    expect([$fresh?->step, $fresh?->printed_by, $fresh?->printed_at !== null])->toBe([CardStepEnum::Printed, $joy->id, true]);
});

it('prints nothing not yet approved, or past Approved', function (CardStepEnum $step): void {
    $request = CardRequest::factory()->create(['step' => $step]);

    expect((new UpdateCardRequestAsPrintedJob(request: $request, by: User::factory()->create()))->handle())->toBeFalse()
        ->and($request->fresh()?->step)->toBe($step);
})->with([CardStepEnum::Asked, CardStepEnum::Printed, CardStepEnum::Given, CardStepEnum::Refused]);
