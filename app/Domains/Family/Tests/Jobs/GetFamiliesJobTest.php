<?php

declare(strict_types=1);

use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Domains\Family\DataObjects\FamilyListFiltersData;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Domains\Family\Jobs\GetFamiliesJob;
use App\Enums\ChildStatusEnum;
use App\Enums\DuplicateStatusEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* The registration list: this church's families in the viewer's satellites, newest first, narrowed by the filters. */

/** @return list<int> the ids the Job lists, in its order */
function listedFamilyIds(GetFamiliesJob $job): array
{
    return array_map(fn (Family $row): int => $row->id, $job->handle()->items);
}

it('lists only this church\'s families, newest first', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $older = familyAt($satellite);
    $newer = familyAt($satellite);
    familyAt(satelliteOf(church()));

    expect(listedFamilyIds(new GetFamiliesJob(church: $church, satelliteIds: null)))->toBe([$newer->id, $older->id]);
});

it('keeps to the viewer\'s satellites, and a chosen satellite outside them yields nothing', function (): void {
    $church = church();
    $mine = satelliteOf($church);
    $theirs = satelliteOf($church);
    $family = familyAt($mine);
    familyAt($theirs);

    $scoped = listedFamilyIds(new GetFamiliesJob(church: $church, satelliteIds: [$mine->id]));
    $outside = listedFamilyIds(new GetFamiliesJob(church: $church, satelliteIds: [$mine->id], filters: new FamilyListFiltersData(satelliteId: $theirs->id)));

    expect($scoped)->toBe([$family->id])->and($outside)->toBeEmpty();
});

it('narrows by search text, a child\'s status and a child\'s level', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $santos = familyAt($satellite);
    guardianOf($santos, User::factory()->create(), ['first_name' => 'Janice', 'last_name' => 'Santos']);
    childOf($santos, ['status' => ChildStatusEnum::Active, 'level' => 2]);
    $reyes = familyAt($satellite);
    guardianOf($reyes, User::factory()->create(), ['first_name' => 'Carlo', 'last_name' => 'Reyes']);
    childOf($reyes, ['status' => ChildStatusEnum::Inactive, 'level' => 3]);

    $list = fn (FamilyListFiltersData $f): array => listedFamilyIds(new GetFamiliesJob(church: $church, satelliteIds: null, filters: $f));

    expect($list(new FamilyListFiltersData(query: 'santos')))->toBe([$santos->id])
        ->and($list(new FamilyListFiltersData(status: ChildStatusEnum::Inactive)))->toBe([$reyes->id])
        ->and($list(new FamilyListFiltersData(level: 2)))->toBe([$santos->id]);
});

it('shows only families whose primary guardian has no confirmed login, when asked', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $confirmed = familyAt($satellite);
    guardianOf($confirmed, User::factory()->create());
    $unconfirmed = familyAt($satellite);
    guardianOf($unconfirmed, User::factory()->unverified()->create());

    $list = listedFamilyIds(new GetFamiliesJob(church: $church, satelliteIds: null, filters: new FamilyListFiltersData(unconfirmedOnly: true)));

    expect($list)->toBe([$unconfirmed->id]);
});

it('shows only families on an open possible duplicate, when asked — by family or by child', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $old = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $old->id, 'phone' => '0501234567']);
    $new = familyAt($satellite);
    Guardian::factory()->create(['family_id' => $new->id, 'phone' => '0501234567']);
    $twinA = familyAt($satellite);
    childOf($twinA, ['first_name' => 'Amara', 'last_name' => 'Cruz', 'date_of_birth' => '2019-01-01']);
    $twinB = familyAt($satellite);
    childOf($twinB, ['first_name' => 'Amara', 'last_name' => 'Cruz', 'date_of_birth' => '2019-01-01']);
    $loner = familyAt($satellite);
    (new CreateDuplicateCandidatesJob(family: $new))->handle();
    (new CreateDuplicateCandidatesJob(family: $twinB))->handle();

    $list = fn (): array => listedFamilyIds(new GetFamiliesJob(church: $church, satelliteIds: null, filters: new FamilyListFiltersData(duplicatesOnly: true)));

    expect($list())->toEqualCanonicalizing([$old->id, $new->id, $twinA->id, $twinB->id]);

    DuplicateCandidate::query()->update(['status' => DuplicateStatusEnum::Dismissed]);

    expect($list())->toBe([]);
});

it('finds a family by phone whatever spaces or + the phone was saved with', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    guardianOf($family, User::factory()->create(), ['phone' => '+971 50 123 4567']);

    $found = fn (string $q) => listedFamilyIds(new GetFamiliesJob(church: $church, satelliteIds: null, filters: new FamilyListFiltersData(query: $q)));

    expect($found('501234567'))->toBe([$family->id])
        ->and($found('50-123-4567'))->toBe([$family->id]);
});

it('hands back the asked-for page of 50, newest first, cut in the database', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $ids = array_reverse(array_map(fn (): int => familyAt($satellite)->id, range(1, 55)));

    $page = (new GetFamiliesJob(church: $church, satelliteIds: null, page: 2))->handle();

    expect(collect($page->items)->pluck('id')->all())->toBe(array_slice($ids, 50))
        ->and([$page->page, $page->lastPage, $page->total])->toBe([2, 2, 55])
        ->and($page->items[0]->relationLoaded('children'))->toBeTrue();
});

it('hands back every filtered family when no page is asked for (the Excel download)', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    foreach (range(1, 55) as $_) {
        familyAt($satellite);
    }

    expect((new GetFamiliesJob(church: $church, satelliteIds: null))->handle()->items)->toHaveCount(55);
});
