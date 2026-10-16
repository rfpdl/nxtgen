<?php

declare(strict_types=1);

use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Domains\Family\Jobs\GetFamilyPortalJob;
use App\Enums\RelationshipEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* What a parent sees on their own family page: home satellite, area, the family's parents and children (oldest first). */

it('shows the family\'s own satellite, parents and children, oldest child first', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church, ['name' => 'CCF DXB']), ['area_text' => 'Al Barsha']);
    guardianOf($family, User::factory()->create(), ['first_name' => 'Maria', 'relationship' => RelationshipEnum::Mother]);
    childOf($family, ['first_name' => 'Ben', 'date_of_birth' => '2020-01-01']);
    childOf($family, ['first_name' => 'Amara', 'date_of_birth' => '2017-06-01']);
    $neighbour = familyAt(satelliteOf($church));
    Guardian::factory()->create(['family_id' => $neighbour->id, 'first_name' => 'Other']);
    childOf($neighbour, ['first_name' => 'Other']);

    $portal = (new GetFamilyPortalJob(family: $family))->handle();

    expect($portal->home_satellite)->toBe('CCF DXB')
        ->and($portal->area)->toBe('Al Barsha')
        ->and(array_map(fn ($g): string => $g->first_name, $portal->guardians))->toBe(['Maria'])
        ->and($portal->guardians[0]->relationship_label)->toBe('Mother')
        ->and(array_map(fn ($c): string => $c->first_name, $portal->children))->toBe(['Amara', 'Ben']);
});

it('hides a child\'s photo until consent is recorded', function (): void {
    $family = familyAt(satelliteOf(church()));
    childOf($family, ['photo_path' => 'photos/a.jpg', 'photo_consent_at' => null]);

    $child = (new GetFamilyPortalJob(family: $family))->handle()->children[0];

    expect($child->photo_consent)->toBeFalse()->and($child->photo_url)->toBeNull();
});
