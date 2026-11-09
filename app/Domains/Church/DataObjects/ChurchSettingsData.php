<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use App\Data\Models\Church;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The church's settings as the platform's form shows them (spec item 12): the card fee in dirhams, "10" or "15.50". */
#[TypeScript]
final class ChurchSettingsData extends Data
{
    public function __construct(
        public string $timezone,
        public string $card_fee,
        public ?string $moving_up_date,
    ) {}

    public static function fromModel(Church $church): self
    {
        $fee = $church->card_fee_minor;

        return new self(
            timezone: $church->timezone,
            card_fee: $fee % 100 === 0 ? (string) intdiv($fee, 100) : sprintf('%d.%02d', intdiv($fee, 100), $fee % 100),
            moving_up_date: $church->moving_up_reference_date?->toDateString(),
        );
    }
}
