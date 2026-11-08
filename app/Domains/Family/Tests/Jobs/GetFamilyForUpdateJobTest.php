<?php

declare(strict_types=1);

use App\Domains\Family\Jobs\GetFamilyForUpdateJob;
use Illuminate\Support\Facades\DB;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A family read under lock, as it is now — where a put back checks which satellite the family belongs to. */

it('reads the family as it is now, inside a transaction', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    $house = satelliteOf($church);
    $family->newQuery()->whereKey($family->id)->update(['home_satellite_id' => $house->id]);

    $found = DB::transaction(fn () => (new GetFamilyForUpdateJob(family: $family))->handle());

    expect($found->home_satellite_id)->toBe($house->id)
        ->and($family->home_satellite_id)->not->toBe($house->id);
});
