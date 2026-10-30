<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Domains\Card\Jobs\UpdateCardRequestAsPaidJob;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;

/* Marking a Card request's AED 10 paid (cash at the desk): once, only while it is open, and never for a free card. */

it('marks an unpaid open request paid, saying who and when', function (CardStepEnum $step): void {
    $request = CardRequest::factory()->create(['step' => $step]);
    $joy = User::factory()->create();
    travelTo(CarbonImmutable::parse('2026-10-31 09:20:00'));

    expect((new UpdateCardRequestAsPaidJob(request: $request, by: $joy))->handle())->toBeTrue();

    $fresh = $request->fresh();
    expect([$fresh?->paid_at?->toDateTimeString(), $fresh?->paid_by, $fresh?->step])->toBe(['2026-10-31 09:20:00', $joy->id, $step]);
})->with([CardStepEnum::Asked, CardStepEnum::Approved, CardStepEnum::Printed]);

it('marks nothing twice, nothing closed and nothing free — and says so', function (array $state): void {
    /** @var array<model-property<CardRequest>, mixed> $state */
    $request = CardRequest::factory()->create($state);
    $before = $request->fresh()?->paid_at?->toDateTimeString();

    expect((new UpdateCardRequestAsPaidJob(request: $request, by: User::factory()->create()))->handle())->toBeFalse()
        ->and($request->fresh()?->paid_at?->toDateTimeString())->toBe($before);
})->with([
    'already paid' => [['paid_at' => '2026-10-24 09:00:00']],
    'given' => [['step' => CardStepEnum::Given]],
    'refused' => [['step' => CardStepEnum::Refused]],
    'a free first card' => [['reason' => CardReasonEnum::FirstCard, 'fee_minor' => 0]],
]);
