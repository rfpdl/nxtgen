<?php

declare(strict_types=1);

namespace App\Services\Platform\Features;

use App\Data\Models\Church;
use App\Data\Models\User;
use App\Foundation\Support\RequestContext;
use Lucid\Units\Feature;

/**
 * What every platform page starts from (spec item 12): the platform admin and the church, both RequirePlatformAdmin's.
 *
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BasePlatformFeature extends Feature
{
    protected function church(): Church
    {
        $church = RequestContext::church(request());
        abort_unless($church instanceof Church, 404);

        return $church;
    }

    protected function platformAdmin(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->is_platform_admin, 404);

        return $user;
    }
}
