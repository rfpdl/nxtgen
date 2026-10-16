<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Photo;

use App\Data\Models\User;
use Lucid\Units\Feature;

/**
 * The child-photo link (/photos/children/{id}): signed and short-lived, opened by a parent of the child's family OR
 * by staff who can see it. RequireGuardian does not run on it, so this is not a BaseFamilyFeature — there is no
 * signed-in Guardian or own Family to hand out. The Feature reads the church FROM the child and checks the viewer.
 *
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BaseChildPhotoFeature extends Feature
{
    /** Whoever opened the link — signed in, or the route's `auth` would have stopped them. */
    protected function viewer(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
