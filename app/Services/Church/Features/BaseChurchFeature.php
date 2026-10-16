<?php

declare(strict_types=1);

namespace App\Services\Church\Features;

use App\Data\Models\Church;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\AccessScope;
use Lucid\Units\Feature;

/**
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BaseChurchFeature extends Feature
{
    protected function church(): Church
    {
        $church = request()->attributes->get('church');
        abort_unless($church instanceof Church, 404);

        return $church;
    }

    protected function scope(): AccessScope
    {
        $scope = request()->attributes->get('scope');
        abort_unless($scope instanceof AccessScope, 404);

        return $scope;
    }

    protected function staff(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
