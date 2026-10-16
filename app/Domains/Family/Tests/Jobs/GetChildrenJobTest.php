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

/** @return list<string> the nicknames the Job lists, in its order */
function listedNicknames(GetChildrenJob $job): array
{
    return array_map(fn (Child $row): string => $row->nickname, $job->handle()->items);
}

it('lists only this church\'s children, by nickname', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    childOf(familyAt($satellite), ['nickname' => 'Zed']);
    childOf(familyAt($satellite), ['nickname' => 'Amara']);
    childOf(familyAt(satelliteOf(church())), ['nickname' => 'Other Church']);

    $children = listedNicknames(new GetChildrenJob(church: $church, satelliteIds: null));

    expect($children)->toBe(['Amara', 'Zed']);
});

it('keeps to the viewer\'s satellites, and a chosen satellite outside them yields nothing', function (): void {
    $church = church();
    $mine = satelliteOf($church);
    $theirs = satelliteOf($church);
    childOf(familyAt($mine), ['nickname' => 'Mine']);
    childOf(familyAt($theirs), ['nickname' => 'Theirs']);

    $scoped = listedNicknames(new GetChildrenJob(church: $church, satelliteIds: [$mine->id]));
    $outside = listedNicknames(new GetChildrenJob(church: $church, satelliteIds: [$mine->id], filters: new ChildListFiltersData(satellite_id: $theirs->id)));

    expect($scoped)->toBe(['Mine'])
        ->and($outside)->toBeEmpty();
});

it('narrows by status and level', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    childOf($family, ['nickname' => 'Active L2', 'status' => ChildStatusEnum::Active, 'level' => 2]);
    childOf($family, ['nickname' => 'Inactive L2', 'status' => ChildStatusEnum::Inactive, 'level' => 2]);
    childOf($family, ['nickname' => 'Active L3', 'status' => ChildStatusEnum::Active, 'level' => 3]);

    $filters = new ChildListFiltersData(status: ChildStatusEnum::Active, level: 2);

    expect(listedNicknames(new GetChildrenJob(church: $church, satelliteIds: null, filters: $filters)))->toBe(['Active L2']);
});

it('searches the child\'s own names or the parent, without a child\'s name dragging in siblings', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $santos = familyAt($satellite);
    guardianOf($santos, User::factory()->create(), ['first_name' => 'Janice', 'last_name' => 'Santos']);
    childOf($santos, ['nickname' => 'Amara', 'first_name' => 'Amara', 'last_name' => 'Santos']);
    childOf($santos, ['nickname' => 'Bea', 'first_name' => 'Beatriz', 'last_name' => 'Santos']);
    childOf(familyAt($satellite), ['nickname' => 'Carlo', 'first_name' => 'Carlo', 'last_name' => 'Reyes']);

    $byChild = listedNicknames(new GetChildrenJob(church: $church, satelliteIds: null, filters: new ChildListFiltersData(query: 'amara')));
    $byParent = listedNicknames(new GetChildrenJob(church: $church, satelliteIds: null, filters: new ChildListFiltersData(query: 'Janice')));

    expect($byChild)->toBe(['Amara'])
        ->and($byParent)->toBe(['Amara', 'Bea']);
});

it('treats % and _ in the search as letters, not wildcards', function (): void {
    $church = church();
    childOf(familyAt(satelliteOf($church)), ['nickname' => 'Amara', 'first_name' => 'Amara', 'last_name' => 'Santos']);

    expect(listedNicknames(new GetChildrenJob(church: $church, satelliteIds: null, filters: new ChildListFiltersData(query: '%'))))->toBeEmpty()
        ->and(Child::query()->count())->toBe(1);
});

it('hands back the asked-for page of 50, by nickname, cut in the database', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    $nicknames = array_map(fn (int $n): string => sprintf('Kid %02d', $n), range(1, 55));
    foreach (array_reverse($nicknames) as $nickname) {
        childOf($family, ['nickname' => $nickname]);
    }

    $page = (new GetChildrenJob(church: $church, satelliteIds: null, page: 2))->handle();

    expect(collect($page->items)->pluck('nickname')->all())->toBe(array_slice($nicknames, 50))
        ->and([$page->page, $page->lastPage, $page->total])->toBe([2, 2, 55]);
});
