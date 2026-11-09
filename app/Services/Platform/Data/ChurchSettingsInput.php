<?php

declare(strict_types=1);

namespace App\Services\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The church's settings as the platform admin types them (spec item 12): its time zone, the card fee in dirhams
 * ("10", "15.50") and the date Moving-Up measures ages on.
 */
#[TypeScript]
final class ChurchSettingsInput extends Data
{
    public function __construct(
        public string $timezone,
        public string $card_fee,
        public string $moving_up_date,
    ) {}

    /** The card fee in fils. */
    public function cardFeeMinor(): int
    {
        return (int) round((float) $this->card_fee * 100);
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'timezone' => ['required', 'string', 'timezone:all'],
            'card_fee' => ['required', 'numeric', 'min:0', 'max:1000', 'decimal:0,2'],
            'moving_up_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'timezone.timezone' => 'Please choose a time zone from the list, e.g. Asia/Dubai.',
            'card_fee.min' => 'The fee cannot be below nothing.',
            'card_fee.numeric' => 'Please type the fee in dirhams, e.g. 10 or 15.50.',
            'card_fee.decimal' => 'Please type the fee in dirhams, e.g. 10 or 15.50.',
            'moving_up_date.date_format' => 'Please choose a date.',
        ];
    }
}
