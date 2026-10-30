<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Domains\Card\Jobs\UpdateCardRequestAsApprovedJob;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;
use function Tests\Support\atTheSameTime;
use function Tests\Support\withCommittedWorld;

/* Approving a Card request for printing: only from Asked, only once paid or free — and only once, even at once. */

it('approves an Asked request that is paid or free, saying who and when', function (array $state): void {
    /** @var array<model-property<CardRequest>, mixed> $state */
    $request = CardRequest::factory()->create($state);
    $joy = User::factory()->create();
    travelTo(CarbonImmutable::parse('2026-10-31 09:20:00'));

    expect((new UpdateCardRequestAsApprovedJob(request: $request, by: $joy))->handle())->toBeTrue();

    $fresh = $request->fresh();
    expect([$fresh?->step, $fresh?->approved_by, $fresh?->approved_at?->toDateTimeString()])->toBe([CardStepEnum::Approved, $joy->id, '2026-10-31 09:20:00']);
})->with([
    'paid' => [['paid_at' => '2026-10-30 10:00:00']],
    'free' => [['reason' => CardReasonEnum::FirstCard, 'fee_minor' => 0]],
]);

it('approves nothing unpaid, and nothing past Asked', function (array $state): void {
    /** @var array<model-property<CardRequest>, mixed> $state */
    $request = CardRequest::factory()->create($state);
    $step = $request->step;

    expect((new UpdateCardRequestAsApprovedJob(request: $request, by: User::factory()->create()))->handle())->toBeFalse()
        ->and([$request->fresh()?->step, $request->fresh()?->approved_at])->toBe([$step, null]);
})->with([
    'not paid' => [[]],
    'already approved' => [['step' => CardStepEnum::Approved, 'paid_at' => '2026-10-30 10:00:00']],
    'refused' => [['step' => CardStepEnum::Refused, 'paid_at' => '2026-10-30 10:00:00']],
]);

it('approves ONCE when two admins approve the same request at the same moment', function (): void {
    $request = CardRequest::factory()->create(['paid_at' => now()]);
    $joy = User::factory()->create();
    $ben = User::factory()->create();

    withCommittedWorld(function () use ($request, $joy, $ben): void {
        $results = atTheSameTime(
            fn (): bool => (new UpdateCardRequestAsApprovedJob(request: $request, by: $joy))->handle(),
            fn (): bool => (new UpdateCardRequestAsApprovedJob(request: $request, by: $ben))->handle(),
        );

        $fresh = CardRequest::query()->sole();
        expect($results)->toEqualCanonicalizing([true, false])
            ->and($fresh->step)->toBe(CardStepEnum::Approved)
            ->and($fresh->approved_by)->toBe($results[0] ? $joy->id : $ben->id);
    });
});
