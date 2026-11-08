<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Link;

use App\Data\Models\SatelliteLink;
use App\Services\Church\Features\BaseChurchFeature;

/**
 * What changing a Link starts from: the Link with this id in this church, at a satellite the admin looks after
 * (AccessScope::canManageLinksAt) — else 404.
 *
 * @template-covariant TResult
 *
 * @extends BaseChurchFeature<TResult>
 */
abstract class BaseLinkFeature extends BaseChurchFeature
{
    protected function scopedLink(SatelliteLink $link): SatelliteLink
    {
        $scope = $this->scope();
        abort_unless($scope->canManageLinks(), 404);
        abort_unless($scope->canManageLinksAt($link->satellite), 404);

        return $link;
    }
}
