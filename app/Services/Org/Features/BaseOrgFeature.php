<?php

declare(strict_types=1);

namespace App\Services\Org\Features;

use App\Data\Models\Organization;
use App\Data\Models\User;
use App\Domains\Organization\DataObjects\AccessScope;
use Lucid\Units\Feature;

/**
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BaseOrgFeature extends Feature
{
    protected function organization(): Organization
    {
        $org = request()->attributes->get('organization');
        abort_unless($org instanceof Organization, 404);

        return $org;
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
