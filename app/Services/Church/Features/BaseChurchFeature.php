<?php

declare(strict_types=1);

namespace App\Services\Church\Features;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\AccessScope;
use App\Domains\Family\Jobs\GetChurchChildJob;
use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Foundation\Support\RequestContext;
use Closure;
use Lucid\Units\Feature;

/**
 * What every staff page starts from: the church in the address and the staff member's AccessScope, both resolved
 * by RequireAssignment (RequestContext), and the child or family an id in the URL names — loaded within this
 * church, then refused unless the scope allows it.
 *
 * Every refusal is a 404, never a 403: a staff page does not confirm that a family it will not show exists.
 *
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BaseChurchFeature extends Feature
{
    protected function church(): Church
    {
        $church = RequestContext::church(request());
        abort_unless($church instanceof Church, 404);

        return $church;
    }

    protected function scope(): AccessScope
    {
        $scope = RequestContext::scope(request());
        abort_unless($scope instanceof AccessScope, 404);

        return $scope;
    }

    protected function staff(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }

    /**
     * The child with this id in this church, if the scope allows what the page does with its family — e.g.
     * `$this->scopedChild($id, $this->scope()->canEditFamily(...))`.
     *
     * @param  Closure(Family): bool  $allows
     */
    protected function scopedChild(int $id, Closure $allows): Child
    {
        $child = $this->run(new GetChurchChildJob(church: $this->church(), id: $id));
        abort_unless($allows($child->family), 404);

        return $child;
    }

    /**
     * The family with this id in this church, if the scope allows what the page does with it.
     *
     * @param  Closure(Family): bool  $allows
     */
    protected function scopedFamily(int $id, Closure $allows): Family
    {
        $family = $this->run(new GetFamilyByIdJob(church: $this->church(), id: $id));
        abort_unless($allows($family), 404);

        return $family;
    }
}
