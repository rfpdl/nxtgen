<?php

declare(strict_types=1);

use App\Domains\Level\Jobs\GetChildrenWithoutLevelCountJob;
use App\Enums\ChildStatusEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* Moving-Up skips an active child with no Level; the page says how many need one first. */

it('counts this satellite\'s active children with no Level', function (): void {
    $church = church();
    $dubai = satelliteOf($church);
    $family = familyAt($dubai);
    childOf($family, ['level' => null]);
    childOf($family, ['level' => null]);
    childOf($family, ['level' => 1]);
    childOf($family, ['level' => null, 'status' => ChildStatusEnum::Inactive]);
    childOf(familyAt(satelliteOf($church)), ['level' => null]);
    childOf(familyAt(satelliteOf(church())), ['level' => null]);

    expect((new GetChildrenWithoutLevelCountJob(satellite: $dubai))->handle())->toBe(2);
});
