<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Landing;

use App\Data\Models\User;
use App\Domains\Church\DataObjects\LandingOptionData;
use App\Domains\Church\Jobs\GetLandingOptionsJob;
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
final class ShowLandingFeature extends BaseAuthFeature
{
    /** The device remembers the "My family / Serve" choice (plan §4.5). Holds one of the person's own URLs. */
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
        $remembered = request()->cookie(self::REMEMBERED);
        $urls = array_map(fn (LandingOptionData $o): string => $o->url, $options);
        if (! request()->boolean('choose') && is_string($remembered) && in_array($remembered, $urls, true)) {
            return redirect($remembered);
        }

        return Inertia::render('Auth/Choose', ['options' => $options]);
    }
}
