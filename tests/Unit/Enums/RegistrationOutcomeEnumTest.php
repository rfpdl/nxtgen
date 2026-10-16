<?php

declare(strict_types=1);

use App\Enums\RegistrationOutcomeEnum;

/* What happened to a registration: a new family, an email already here, or a link sent to connect an account. */

it('stores the four outcomes', function (): void {
    expect(array_column(RegistrationOutcomeEnum::cases(), 'value'))
        ->toBe(['registered', 'already_registered', 'claim_sent', 'claim_resent']);
});

it('tells the public form the same thing whatever happened, so it never reveals who is registered', function (): void {
    $messages = array_unique(array_map(fn (RegistrationOutcomeEnum $o): string => $o->guardianMessage(), RegistrationOutcomeEnum::cases()));

    expect($messages)->toHaveCount(1)
        ->and(RegistrationOutcomeEnum::Registered->guardianMessage())->toContain('check your email');
});

it('gives each outcome its label and colour', function (RegistrationOutcomeEnum $outcome, string $label, string $color): void {
    expect([$outcome->label(), $outcome->color()])->toBe([$label, $color]);
})->with([
    [RegistrationOutcomeEnum::Registered, 'Registered', 'success'],
    [RegistrationOutcomeEnum::AlreadyRegistered, 'Already registered', 'info'],
    [RegistrationOutcomeEnum::ClaimSent, 'Claim sent', 'info'],
    [RegistrationOutcomeEnum::ClaimResent, 'Claim re-sent', 'info'],
]);
