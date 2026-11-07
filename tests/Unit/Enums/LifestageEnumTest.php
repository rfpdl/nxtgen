<?php

declare(strict_types=1);

use App\Enums\LifestageEnum;

/* A family's lifestage (Roy 2026-11-07): Single, Married or KSP — or none given. */

it('offers Single, Married and KSP, in that order @story#184', function (): void {
    expect(array_column(LifestageEnum::cases(), 'value'))->toBe(['single', 'married', 'ksp'])
        ->and(array_column(LifestageEnum::toFrontend(), 'label'))->toBe(['Single', 'Married', 'KSP']);
});

it('reads a Registry cell in any capitals and spacing @story#184', function (): void {
    expect(LifestageEnum::fromLoose('MARRIED'))->toBe(LifestageEnum::Married)
        ->and(LifestageEnum::fromLoose(' Single '))->toBe(LifestageEnum::Single)
        ->and(LifestageEnum::fromLoose('ksp'))->toBe(LifestageEnum::Ksp);
});

it('reads anything else as none given @story#185', function (): void {
    expect(LifestageEnum::fromLoose('n/a'))->toBeNull()
        ->and(LifestageEnum::fromLoose('Widowed'))->toBeNull()
        ->and(LifestageEnum::fromLoose(''))->toBeNull()
        ->and(LifestageEnum::fromLoose(null))->toBeNull();
});
