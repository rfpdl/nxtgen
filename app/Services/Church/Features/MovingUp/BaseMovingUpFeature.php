<?php

declare(strict_types=1);

namespace App\Services\Church\Features\MovingUp;

use App\Data\Models\MovingUp;
use App\Domains\Church\Jobs\GetChurchForUpdateJob;
use App\Domains\Level\Jobs\GetLastMovingUpJob;
use App\Services\Church\Features\BaseChurchFeature;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * What putting a Moving-Up back starts from (DECISIONS 2026-11-06): an admin of this satellite, and its LAST Moving-Up
 * with the id in the URL — an earlier one, or another satellite's or church's, is not found — checked with the church's row locked.
 *
 * @template-covariant TResult
 *
 * @extends BaseChurchFeature<TResult>
 */
abstract class BaseMovingUpFeature extends BaseChurchFeature
{
    /**
     * Run `$then` with the last Moving-Up, in one transaction that first locks the church's row — so a new Moving-Up
     * cannot land between "this is the last one" and putting it back (Astra).
     *
     * @template T
     *
     * @param  Closure(MovingUp): T  $then
     * @return T
     */
    protected function withLastMovingUp(MovingUp $movingUp, Closure $then): mixed
    {
        abort_unless($this->scope()->canMoveUp(), 404);

        return DB::transaction(function () use ($movingUp, $then): mixed {
            $this->run(new GetChurchForUpdateJob(church: $this->church()));
            $last = $this->run(new GetLastMovingUpJob(satellite: $this->satellite()));
            abort_unless($last instanceof MovingUp && $last->id === $movingUp->id, 404);

            return $then($last);
        });
    }
}
