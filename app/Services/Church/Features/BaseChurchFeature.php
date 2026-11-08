<?php

declare(strict_types=1);

namespace App\Services\Church\Features;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\AccessScope;
use App\Foundation\Support\RequestContext;
use App\Foundation\Support\RouteArea;
use Closure;
use Illuminate\Http\RedirectResponse;
use Lucid\Units\Feature;

/**
 * What every staff page starts from: the satellite in the address, its church and the staff member's AccessScope
 * there, all resolved by RequireAssignment (RequestContext), and the child or family an id in the URL names — loaded
 * within this church by its route binding (RouteBindingServiceProvider), then refused unless the scope allows it.
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

    protected function satellite(): Satellite
    {
        $satellite = RequestContext::satellite(request());
        abort_unless($satellite instanceof Satellite, 404);

        return $satellite;
    }

    protected function scope(): AccessScope
    {
        $scope = RequestContext::scope(request());
        abort_unless($scope instanceof AccessScope, 404);

        return $scope;
    }

    /**
     * A redirect to `$name` (e.g. `families.show`) in the area this request is in (RouteArea), at this satellite, so a
     * page shown in both Admin and Serve stays where it was.
     *
     * @param  list<mixed>  $parameters  the route's parameters after the satellite
     */
    protected function toArea(string $name, array $parameters = []): RedirectResponse
    {
        return redirect()->route(RouteArea::name(request(), $name), [$this->satellite(), ...$parameters]);
    }

    protected function staff(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }

    /**
     * The address's child, if the scope allows what the page does with its family — e.g.
     * `$this->scopedChild($this->child, $this->scope()->canEditFamily(...))`.
     *
     * @param  Closure(Family): bool  $allows
     */
    protected function scopedChild(Child $child, Closure $allows): Child
    {
        abort_unless($allows($child->family), 404);

        return $child;
    }

    /**
     * The address's family, if the scope allows what the page does with it.
     *
     * @param  Closure(Family): bool  $allows
     */
    protected function scopedFamily(Family $family, Closure $allows): Family
    {
        abort_unless($allows($family), 404);

        return $family;
    }
}
