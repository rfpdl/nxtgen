<?php

declare(strict_types=1);

namespace App\Services\Platform\Features\Settings;

use App\Domains\Church\Jobs\UpdateChurchSettingsJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Platform\Data\ChurchSettingsInput;
use App\Services\Platform\Features\BasePlatformFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Save the church's settings (spec item 12). A card already asked for keeps its fee.
 *
 * @extends BasePlatformFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Save the church\'s settings',
    persona: 'platform admin',
    happyPaths: ['story#199' => 'The platform admin sets the time zone, the card fee and the Moving-Up date; a card asked for then costs the new fee'],
    unhappyPaths: ['story#200' => 'A time zone that is not one, a fee below nothing or a date that is not one is refused'],
)]
final class UpdateSettingsFeature extends BasePlatformFeature
{
    public function handle(ChurchSettingsInput $input): RedirectResponse
    {
        $this->run(new UpdateChurchSettingsJob(
            church: $this->church(),
            timezone: $input->timezone,
            cardFeeMinor: $input->cardFeeMinor(),
            movingUpDate: $input->moving_up_date,
        ));

        return redirect()->route('platform.settings.show')->with('success', 'Settings saved.');
    }
}
