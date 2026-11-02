<?php

declare(strict_types=1);

use App\Domains\Family\Jobs\DeleteOrphanChildPhotosJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

use function Tests\Support\church;

/*
 * The photo sweep (TODO #22): a stored photo file no child points at is never shown, so it goes — a replaced photo
 * whose old file could not be deleted, an upload whose save failed. Only this church's folder, only files older
 * than the cut-off (an upload writes its file a moment before it saves the pointer), never one a child points at.
 */

function storedPhoto(string $path, string $at): void
{
    Storage::disk('photos')->put($path, 'jpeg');
    touch(Storage::disk('photos')->path($path), CarbonImmutable::parse($at)->getTimestamp());
}

beforeEach(fn () => Storage::fake('photos'));

it('deletes this church\'s photo files nobody points at, older than the cut-off, and says which', function (): void {
    $church = church();
    $dir = "children/{$church->id}";
    storedPhoto("{$dir}/1-kept.jpg", '2026-11-01 09:00');
    storedPhoto("{$dir}/1-orphan.jpg", '2026-11-01 09:00');
    storedPhoto("{$dir}/2-just-uploaded.jpg", '2026-11-03 20:59');
    storedPhoto('children/999/3-other-church.jpg', '2026-11-01 09:00');

    $deleted = (new DeleteOrphanChildPhotosJob(church: $church, keep: ["{$dir}/1-kept.jpg"], before: CarbonImmutable::parse('2026-11-02 21:00'), dryRun: false))->handle();

    expect($deleted)->toBe(["{$dir}/1-orphan.jpg"])
        ->and(Storage::disk('photos')->allFiles())->toEqualCanonicalizing(["{$dir}/1-kept.jpg", "{$dir}/2-just-uploaded.jpg", 'children/999/3-other-church.jpg']);
});

it('only says which it would delete on a dry run', function (): void {
    $church = church();
    storedPhoto("children/{$church->id}/1-orphan.jpg", '2026-11-01 09:00');

    $deleted = (new DeleteOrphanChildPhotosJob(church: $church, keep: [], before: CarbonImmutable::parse('2026-11-02 21:00'), dryRun: true))->handle();

    expect($deleted)->toBe(["children/{$church->id}/1-orphan.jpg"])
        ->and(Storage::disk('photos')->allFiles())->toBe(["children/{$church->id}/1-orphan.jpg"]);
});

it('finds nothing in a church with no photos yet', function (): void {
    expect((new DeleteOrphanChildPhotosJob(church: church(), keep: [], before: CarbonImmutable::now(), dryRun: false))->handle())->toBe([]);
});
