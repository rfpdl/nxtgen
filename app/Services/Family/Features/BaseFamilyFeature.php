<?php

declare(strict_types=1);

namespace App\Services\Family\Features;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Foundation\Support\RequestContext;
use Lucid\Units\Feature;

/**
 * What every parent page starts from: the signed-in Guardian, resolved by RequireGuardian (RequestContext) — never
 * from request input — and so their own Family. Anything missing is a 404, as in the middleware.
 *
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BaseFamilyFeature extends Feature
{
    /** The signed-in parent's Guardian row in this church, with its family and church loaded. */
    protected function guardian(): Guardian
    {
        $guardian = RequestContext::guardian(request());
        abort_unless($guardian instanceof Guardian, 404);

        return $guardian;
    }

    /** The signed-in parent's own family. */
    protected function family(): Family
    {
        return $this->guardian()->family;
    }

    /** The address's child, if it is of the parent's OWN family; any other is a 404. */
    protected function ownChild(Child $child): Child
    {
        abort_unless($child->family_id === $this->family()->id, 404);

        return $child;
    }

    /** The signed-in parent's login — who a consent or a change is recorded as. */
    protected function guardianUser(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
