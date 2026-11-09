<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Landing;

use App\Data\Models\User;
use App\Domains\Church\DataObjects\LandingOptionData;
use App\Domains\Church\Jobs\GetLandingOptionsJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

/**
 * "My family" or "Serve" on the choice page: go there, and remember it on this device (plan §4.5). Only one of
 * the person's own options is accepted — never an address from the request — so this cannot redirect off-site.
 *
 * @extends BaseAuthFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Choose where to go',
    persona: 'anyone signed in',
    happyPaths: ['story#26' => 'The choice is remembered until they switch'],
    unhappyPaths: ['story#27' => 'A choice that is not theirs is refused or forgotten'],
)]
final class ChooseLandingFeature extends BaseAuthFeature
{
    public function handle(): RedirectResponse
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);

        // A place, not an address (Astra, issue 08): it is resolved now, to where that place is today.
        $wanted = request()->string('place')->value();
        $option = array_values(array_filter($this->run(new GetLandingOptionsJob(user: $user)), fn (LandingOptionData $o): bool => $o->place === $wanted))[0] ?? null;
        abort_if($option === null, 404);

        Cookie::queue(ShowLandingFeature::REMEMBERED, $option->place, 60 * 24 * 365);

        return redirect($option->url);
    }
}
