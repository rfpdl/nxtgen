<?php

declare(strict_types=1);

namespace App\Services\Platform\Features\Settings;

use App\Domains\Church\DataObjects\ChurchSettingsData;
use App\Foundation\Attributes\UserStory;
use App\Services\Platform\DataObjects\PlatformSettingsPageData;
use App\Services\Platform\Features\BasePlatformFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The church's settings (spec item 12): its time zone, the card fee and the Moving-Up date.
 *
 * @extends BasePlatformFeature<Response>
 */
#[UserStory(
    title: 'See the church\'s settings',
    persona: 'platform admin',
    happyPaths: ['story#201' => 'The platform admin sees the time zone, the card fee and the Moving-Up date'],
)]
final class ShowSettingsFeature extends BasePlatformFeature
{
    public function handle(): Response
    {
        return Inertia::render('Platform/Settings', new PlatformSettingsPageData(
            settings: ChurchSettingsData::fromModel($this->church()),
            timezones: timezone_identifiers_list(),
        ));
    }
}
