<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Data\Models\CardRequest;
use App\Data\Models\Family;
use App\Domains\Card\DataObjects\CardRequestData;
use App\Domains\Card\Jobs\GetCardRequestJob;
use App\Services\Church\Features\BaseChurchFeature;
use Closure;
use Illuminate\Http\RedirectResponse;

/**
 * What moving one Card request starts from (name cards 03): the request with the id in the URL, in this church, if
 * the scope allows the move on the child's family — else a 404, like every staff refusal. And one answer for a move
 * that found the request elsewhere (another admin, another tab, a stale page): it says where it is now.
 *
 * @template-covariant TResult
 *
 * @extends BaseChurchFeature<TResult>
 */
abstract class BaseCardRequestFeature extends BaseChurchFeature
{
    /** @param  Closure(Family): bool  $allows */
    protected function scopedRequest(int $id, Closure $allows): CardRequest
    {
        $request = $this->run(new GetCardRequestJob(church: $this->church(), id: $id));
        abort_unless($allows($request->child->family), 404);

        return $request;
    }

    /** `$done` when the move landed; otherwise where the request is now, in its Pill's words. */
    protected function answer(bool $moved, CardRequest $request, string $done): RedirectResponse
    {
        if ($moved) {
            return back()->with('success', $done);
        }

        $now = $this->run(new GetCardRequestJob(church: $this->church(), id: $request->id));

        return back()->with('error', "{$request->child->displayName()}'s card has already moved on: ".CardRequestData::fromModel($now)->summary.'.');
    }
}
