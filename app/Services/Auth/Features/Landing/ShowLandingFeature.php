<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Landing;

use App\Data\Models\User;
use App\Domains\Organization\Jobs\GetLandingOptionsJob;
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

        return Inertia::render('Auth/Choose', ['options' => $options]);
    }
}
