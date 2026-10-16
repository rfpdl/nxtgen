<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Data\Models\Church;
use App\Data\Models\Guardian;
use App\Domains\Church\DataObjects\AccessScope;
use Illuminate\Http\Request;

/**
 * What a service middleware resolved about the request, kept on it for the Features — in ONE place, so no Feature
 * or middleware spells an attribute name. `RequireAssignment` puts the church and the staff member's AccessScope;
 * `RequireGuardian` puts the church and the signed-in Guardian (whose Family the parent pages act on).
 *
 * Getters answer null when nothing (or something else) was put; the base Features turn that into a 404.
 */
final class RequestContext
{
    private const string CHURCH = 'church';

    private const string SCOPE = 'scope';

    private const string GUARDIAN = 'guardian';

    public static function putChurch(Request $request, Church $church): void
    {
        $request->attributes->set(self::CHURCH, $church);
    }

    public static function church(Request $request): ?Church
    {
        $church = $request->attributes->get(self::CHURCH);

        return $church instanceof Church ? $church : null;
    }

    public static function putScope(Request $request, AccessScope $scope): void
    {
        $request->attributes->set(self::SCOPE, $scope);
    }

    public static function scope(Request $request): ?AccessScope
    {
        $scope = $request->attributes->get(self::SCOPE);

        return $scope instanceof AccessScope ? $scope : null;
    }

    public static function putGuardian(Request $request, Guardian $guardian): void
    {
        $request->attributes->set(self::GUARDIAN, $guardian);
    }

    public static function guardian(Request $request): ?Guardian
    {
        $guardian = $request->attributes->get(self::GUARDIAN);

        return $guardian instanceof Guardian ? $guardian : null;
    }
}
