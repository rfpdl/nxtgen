<?php

declare(strict_types=1);

use App\Data\Models\RegistryImportMapping;
use App\Domains\Registry\Jobs\CreateImportMappingJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* Which sheet row became which child — in the child's own church and family, so a rerun can skip it. */

it('maps the sheet row to the child, in the child\'s church and family', function (): void {
    Carbon::setTestNow('2026-10-17 09:30:00');
    $child = childOf(familyAt(satelliteOf(church())));

    (new CreateImportMappingJob(child: $child, sourceKey: 'row-key-1', familyKey: 'family-key-1'))->handle();

    $mapping = RegistryImportMapping::query()->sole();
    expect([$mapping->church_id, $mapping->family_id, $mapping->child_id])->toBe([$child->church_id, $child->family_id, $child->id])
        ->and([$mapping->source_child_key, $mapping->approved_family_key])->toBe(['row-key-1', 'family-key-1'])
        ->and($mapping->imported_at)->toEqual(CarbonImmutable::parse('2026-10-17 09:30:00'));
});
