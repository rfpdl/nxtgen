<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Domains\Card\Jobs\UpdateCardRequestAsRefusedJob;
use App\Enums\CardStepEnum;

/* Refusing a Card request, with a short reason: before it is printed only. Refused closes it, freeing the Child. */

it('refuses an Asked or Approved request with its reason, who and when — and frees the child', function (CardStepEnum $step): void {
    $request = CardRequest::factory()->create(['step' => $step]);
    $joy = User::factory()->create();

    expect((new UpdateCardRequestAsRefusedJob(request: $request, reason: 'Found it at home', by: $joy))->handle())->toBeTrue();

    $fresh = $request->fresh();
    expect([$fresh?->step, $fresh?->refused_reason, $fresh?->refused_by, $fresh?->refused_at !== null, $fresh?->open_child_id])
        ->toBe([CardStepEnum::Refused, 'Found it at home', $joy->id, true, null]);
})->with([CardStepEnum::Asked, CardStepEnum::Approved]);

it('refuses nothing printed or closed', function (CardStepEnum $step): void {
    $request = CardRequest::factory()->create(['step' => $step]);

    expect((new UpdateCardRequestAsRefusedJob(request: $request, reason: 'Late', by: User::factory()->create()))->handle())->toBeFalse()
        ->and([$request->fresh()?->step, $request->fresh()?->refused_reason])->toBe([$step, null]);
})->with([CardStepEnum::Printed, CardStepEnum::Given, CardStepEnum::Refused]);
