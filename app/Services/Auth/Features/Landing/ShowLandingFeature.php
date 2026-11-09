<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Landing;

use App\Data\Models\User;
use App\Domains\Church\DataObjects\LandingOptionData;
use App\Domains\Church\Jobs\GetLandingOptionsJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The ONE place that decides where a signed-in person lands (plan §4.5, PoC lesson: a second place
 * computing it drifts). No role picker: one destination → go there; several → choose; none → say so.
 *
 * @extends BaseAuthFeature<Response|RedirectResponse>
 */
#[UserStory(
    title: 'Land in the right place after sign-in',
    persona: 'anyone signed in',
    happyPaths: ['story#24' => 'A parent goes to their Family, Staff to the Church, and someone with both chooses'],
    unhappyPaths: ['story#25' => 'Someone with no Family and no active Role is told there is nothing here yet'],
)]
final class ShowLandingFeature extends BaseAuthFeature
{
    /** The device remembers the "My family / Serve" choice (plan §4.5). Holds a place (`family`, `serve:{church id}`), never an address. */
    public const string REMEMBERED = 'nxtgen_landing';

    public function handle(): Response|RedirectResponse
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);

        $options = $this->run(new GetLandingOptionsJob(user: $user));

        if ($options === []) {
            return Inertia::render('Auth/NothingYet');
        }

        if (count($options) === 1) {
            return redirect($options[0]->url);
        }

        // A remembered choice counts only while it is STILL one of their options, and only as an exact match —
        // the cookie can never send anyone anywhere else. `?choose=1` (the Switch link) asks again.
        // The remembered PLACE (My family, Serve), sent to where it is today — Serve follows the satellite used last.
        $remembered = request()->cookie(self::REMEMBERED);
        $option = array_values(array_filter($options, fn (LandingOptionData $o): bool => $o->place === $remembered))[0] ?? null;
        if (! request()->boolean('choose') && $option !== null) {
            return redirect($option->url);
        }

        return Inertia::render('Auth/Choose', ['options' => $options]);
    }
}
