<?php

declare(strict_types=1);

use App\Domains\Family\Jobs\GetChildPhotoPathsJob;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * Every stored photo this church's children point at — a hidden one too (consent taken back hides the photo but
 * keeps it, Roy 2026-10-16), so the photo sweep never deletes a photo that would come back on a new yes.
 */

it('lists the photo every child of this church points at, hidden ones included, and no other church\'s', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    childOf($family, ['photo_path' => 'children/1/amara.jpg', 'photo_consent_at' => now()]);
    childOf($family, ['photo_path' => 'children/1/ben.jpg', 'photo_consent_at' => null]);
    childOf($family, ['photo_path' => null]);
    childOf(familyAt(satelliteOf(church())), ['photo_path' => 'children/2/pia.jpg']);

    $paths = (new GetChildPhotoPathsJob(church: $church))->handle();

    expect($paths)->toEqualCanonicalizing(['children/1/amara.jpg', 'children/1/ben.jpg']);
});
