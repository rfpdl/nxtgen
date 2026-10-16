<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Church\DataObjects\AccessScope;
use App\Foundation\Support\RequestContext;
use Illuminate\Http\Request;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/*
 * What the service middleware resolved (the church, the staff member's AccessScope, the signed-in guardian), kept on
 * the request behind typed getters — the one place that knows the attribute names.
 */

it('hands back the church, scope and guardian a middleware put on the request', function (): void {
    $church = church();
    $guardian = guardianOf(familyAt(satelliteOf($church)), User::factory()->create());
    $scope = new AccessScope($church->id, true, false, [], [], []);
    $request = Request::create('/ccf');

    RequestContext::putChurch($request, $church);
    RequestContext::putScope($request, $scope);
    RequestContext::putGuardian($request, $guardian);

    expect(RequestContext::church($request))->toBe($church)
        ->and(RequestContext::scope($request))->toBe($scope)
        ->and(RequestContext::guardian($request))->toBe($guardian);
});

it('answers null when nothing was resolved, or something else sits under the name', function (): void {
    $request = Request::create('/ccf');
    $request->attributes->set('church', 'ccf');

    expect(RequestContext::church($request))->toBeNull()
        ->and(RequestContext::scope($request))->toBeNull()
        ->and(RequestContext::guardian($request))->toBeNull();
});
