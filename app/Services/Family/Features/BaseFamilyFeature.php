<?php

declare(strict_types=1);

namespace App\Services\Family\Features;

use App\Data\Models\Family;
use Lucid\Units\Feature;

/**
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BaseFamilyFeature extends Feature
{
    /** The signed-in parent's own family, resolved by RequireGuardian — never from request input. */
    protected function family(): Family
    {
        $family = request()->attributes->get('family');
        abort_unless($family instanceof Family, 404);

        return $family;
    }
}
