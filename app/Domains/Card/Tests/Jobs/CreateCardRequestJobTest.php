<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\Child;
use App\Data\Models\User;
use App\Domains\Card\Jobs\CreateCardRequestJob;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\travelTo;
use function Tests\Support\atTheSameTime;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\withCommittedWorld;

/*
 * Filing a Card request (GLOSSARY "Card request"): one ask for a new Name card for one Child, starting at Asked, with
 * its fee — and never a second open one for the same Child, even when two staff file at the same moment.
 */

function cardChild(): Child
{
    return childOf(familyAt(satelliteOf(church())), ['nickname' => 'Amara']);
}

it('files a request at Asked, with the reason, note, fee, who and when — not paid', function (): void {
    $amara = cardChild();
    $joy = User::factory()->create();
    travelTo(CarbonImmutable::parse('2026-10-31 09:20:00'));

    $request = (new CreateCardRequestJob(child: $amara, reason: CardReasonEnum::Lost, note: 'Lost at the park', by: $joy))->handle();

    $row = CardRequest::query()->sole();
    expect($request->is($row))->toBeTrue()
        ->and($request->wasRecentlyCreated)->toBeTrue()
        ->and([$row->church_id, $row->child_id, $row->reason, $row->note, $row->step, $row->fee_minor, $row->paid_at, $row->created_by, $row->created_at?->toDateTimeString()])
        ->toBe([$amara->church_id, $amara->id, CardReasonEnum::Lost, 'Lost at the park', CardStepEnum::Asked, 1000, null, $joy->id, '2026-10-31 09:20:00']);
});

it('charges nothing for a first card', function (): void {
    $request = (new CreateCardRequestJob(child: cardChild(), reason: CardReasonEnum::FirstCard, note: null, by: User::factory()->create()))->handle();

    expect([$request->reason, $request->fee_minor, $request->note])->toBe([CardReasonEnum::FirstCard, 0, null]);
});

it('hands back the open request instead of filing a second one for the same Child', function (CardStepEnum $step): void {
    $amara = cardChild();
    $open = CardRequest::factory()->for($amara)->create(['step' => $step]);

    $again = (new CreateCardRequestJob(child: $amara, reason: CardReasonEnum::Damaged, note: 'Torn', by: User::factory()->create()))->handle();

    expect($again->is($open))->toBeTrue()
        ->and($again->wasRecentlyCreated)->toBeFalse()
        ->and($again->reason)->toBe(CardReasonEnum::Lost)
        ->and(CardRequest::query()->count())->toBe(1);
})->with([CardStepEnum::Asked, CardStepEnum::Approved, CardStepEnum::Printed]);

it('files a new request once the last one is closed — Given or Refused', function (CardStepEnum $step): void {
    $amara = cardChild();
    CardRequest::factory()->for($amara)->create(['step' => $step]);

    $new = (new CreateCardRequestJob(child: $amara, reason: CardReasonEnum::Lost, note: null, by: User::factory()->create()))->handle();

    expect($new->wasRecentlyCreated)->toBeTrue()
        ->and(CardRequest::query()->where('child_id', $amara->id)->count())->toBe(2);
})->with([CardStepEnum::Given, CardStepEnum::Refused]);

it('keeps one open request per Child: another Child has their own', function (): void {
    $amara = cardChild();
    $ben = childOf($amara->family, ['nickname' => 'Ben']);
    $joy = User::factory()->create();

    (new CreateCardRequestJob(child: $amara, reason: CardReasonEnum::Lost, note: null, by: $joy))->handle();
    (new CreateCardRequestJob(child: $ben, reason: CardReasonEnum::Lost, note: null, by: $joy))->handle();

    expect(CardRequest::query()->orderBy('id')->pluck('child_id')->all())->toBe([$amara->id, $ben->id]);
});

/**
 * File inside a caller's transaction that has already read (as a registration will, issue 02): its MySQL snapshot is
 * then taken BEFORE the other phone's row lands, so only a locking read can see that row once the key refuses.
 *
 * @return array{0: int, 1: bool} the request's id, and whether this call made it
 */
function fileInTransaction(Child $child, CardReasonEnum $reason, User $by): array
{
    return DB::transaction(function () use ($child, $reason, $by): array {
        CardRequest::query()->where('church_id', $child->church_id)->count();
        $request = (new CreateCardRequestJob(child: $child, reason: $reason, note: null, by: $by))->handle();

        return [$request->id, $request->wasRecentlyCreated];
    });
}

it('makes ONE request when two staff file for the same Child at the same moment, each inside its own transaction', function (): void {
    $amara = cardChild();
    $joy = User::factory()->create();
    $ben = User::factory()->create();

    withCommittedWorld(function () use ($amara, $joy, $ben): void {
        $results = atTheSameTime(
            fn (): array => fileInTransaction($amara, CardReasonEnum::Lost, $joy),
            fn (): array => fileInTransaction($amara, CardReasonEnum::Damaged, $ben),
        );

        // Both are told about the same one request; exactly one of them made it.
        $request = CardRequest::query()->sole();
        expect(array_column($results, 0))->toBe([$request->id, $request->id])
            ->and(array_column($results, 1))->toEqualCanonicalizing([true, false]);
    });
});
