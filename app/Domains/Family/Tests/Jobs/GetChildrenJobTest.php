<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\ChildListFiltersData;
use App\Domains\Family\Jobs\GetChildrenJob;
use App\Enums\ChildStatusEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* The children list: this church's children whose family's home satellite the viewer sees, narrowed by the filters. */

it('lists only this church\'s children, by nickname', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    childOf(familyAt($satellite), ['nickname' => 'Zed']);
    childOf(familyAt($satellite), ['nickname' => 'Amara']);
    childOf(familyAt(satelliteOf(church())), ['nickname' => 'Other Church']);

    $children = (new GetChildrenJob(church: $church, satelliteIds: null))->handle();

    expect($children->pluck('nickname')->all())->toBe(['Amara', 'Zed']);
});

it('keeps to the viewer\'s satellites, and a chosen satellite outside them yields nothing', function (): void {
    $church = church();
    $mine = satelliteOf($church);
    $theirs = satelliteOf($church);
    childOf(familyAt($mine), ['nickname' => 'Mine']);
    childOf(familyAt($theirs), ['nickname' => 'Theirs']);

    $scoped = (new GetChildrenJob(church: $church, satelliteIds: [$mine->id]))->handle();
    $outside = (new GetChildrenJob(church: $church, satelliteIds: [$mine->id], filters: new ChildListFiltersData(satellite_id: $theirs->id)))->handle();

    expect($scoped->pluck('nickname')->all())->toBe(['Mine'])
        ->and($outside)->toBeEmpty();
});

it('narrows by status and level', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    childOf($family, ['nickname' => 'Active L2', 'status' => ChildStatusEnum::Active, 'level' => 2]);
    childOf($family, ['nickname' => 'Inactive L2', 'status' => ChildStatusEnum::Inactive, 'level' => 2]);
    childOf($family, ['nickname' => 'Active L3', 'status' => ChildStatusEnum::Active, 'level' => 3]);

    $filters = new ChildListFiltersData(status: ChildStatusEnum::Active, level: 2);

    expect((new GetChildrenJob(church: $church, satelliteIds: null, filters: $filters))->handle()->pluck('nickname')->all())->toBe(['Active L2']);
});

it('searches the child\'s own names or the parent, without a child\'s name dragging in siblings', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $santos = familyAt($satellite);
    guardianOf($santos, User::factory()->create(), ['first_name' => 'Janice', 'last_name' => 'Santos']);
    childOf($santos, ['nickname' => 'Amara', 'first_name' => 'Amara', 'last_name' => 'Santos']);
    childOf($santos, ['nickname' => 'Bea', 'first_name' => 'Beatriz', 'last_name' => 'Santos']);
    childOf(familyAt($satellite), ['nickname' => 'Carlo', 'first_name' => 'Carlo', 'last_name' => 'Reyes']);

    $byChild = (new GetChildrenJob(church: $church, satelliteIds: null, filters: new ChildListFiltersData(query: 'amara')))->handle();
    $byParent = (new GetChildrenJob(church: $church, satelliteIds: null, filters: new ChildListFiltersData(query: 'Janice')))->handle();

    expect($byChild->pluck('nickname')->all())->toBe(['Amara'])
        ->and($byParent->pluck('nickname')->all())->toBe(['Amara', 'Bea']);
});

it('treats % and _ in the search as letters, not wildcards', function (): void {
    $church = church();
    childOf(familyAt(satelliteOf($church)), ['nickname' => 'Amara', 'first_name' => 'Amara', 'last_name' => 'Santos']);

    expect((new GetChildrenJob(church: $church, satelliteIds: null, filters: new ChildListFiltersData(query: '%')))->handle())->toBeEmpty()
        ->and(Child::query()->count())->toBe(1);
});
