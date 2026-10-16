<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Domains\Registry\Jobs\CreateImportMappingJob;
use App\Domains\Registry\Jobs\GetImportedSourceKeysJob;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* The sheet rows a church has already imported, so a rerun skips them — never another church's. */

function importedChild(Child $child, string $sourceKey): void
{
    (new CreateImportMappingJob(child: $child, sourceKey: $sourceKey, familyKey: 'fk-'.$sourceKey))->handle();
}

it('maps each imported row to its family, for this church only', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    importedChild(childOf($family), 'ours-1');
    importedChild(childOf($family), 'ours-2');
    importedChild(childOf(familyAt(satelliteOf(church()))), 'theirs');

    $keys = (new GetImportedSourceKeysJob(church: $church))->handle();

    expect($keys)->toBe(['ours-1' => $family->id, 'ours-2' => $family->id]);
});

it('is empty for a church that has imported nothing', function (): void {
    importedChild(childOf(familyAt(satelliteOf(church()))), 'elsewhere');

    expect((new GetImportedSourceKeysJob(church: church()))->handle())->toBe([]);
});
