<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * Save the church's settings (spec item 12): its time zone, the card fee for a lost or damaged card (in fils) and the
 * date Moving-Up measures ages on. A card already asked for keeps the fee it was asked at.
 *
 * @extends BaseJob<void>
 */
final class UpdateChurchSettingsJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly string $timezone,
        private readonly int $cardFeeMinor,
        private readonly string $movingUpDate,
    ) {}

    public function handle(): void
    {
        $this->church->forceFill([
            'timezone' => $this->timezone,
            'card_fee_minor' => $this->cardFeeMinor,
            'moving_up_reference_date' => $this->movingUpDate,
        ])->save();
    }
}
