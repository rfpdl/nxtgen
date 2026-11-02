<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\PendingCommand;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * `nxtgen:photos-sweep` (TODO #22): once a day, every church's photo files that no child points at — and that are
 * more than a day old — are deleted. Photos a child points at stay, a hidden one too (consent taken back hides, it
 * does not delete — SECURITY #19). `--dry-run` only lists them.
 */

function sweptPhoto(string $path, string $at): void
{
    Storage::disk('photos')->put($path, 'jpeg');
    touch(Storage::disk('photos')->path($path), CarbonImmutable::parse($at)->getTimestamp());
}

/** @param array<string, bool> $options */
function sweep(array $options): PendingCommand
{
    $pending = artisan('nxtgen:photos-sweep', $options);

    return $pending instanceof PendingCommand ? $pending : throw new RuntimeException('The command ran without a test double.');
}

beforeEach(function (): void {
    Storage::fake('photos');
    travelTo(CarbonImmutable::parse('2026-11-03 21:00'));
});

it('deletes every church\'s day-old photo files no child points at, and keeps the rest @story#152', function (): void {
    $ccf = church(['slug' => 'ccf']);
    $other = church(['slug' => 'other']);
    childOf(familyAt(satelliteOf($ccf)), ['photo_path' => "children/{$ccf->id}/1-amara.jpg", 'photo_consent_at' => now()]);
    childOf(familyAt(satelliteOf($ccf)), ['photo_path' => "children/{$ccf->id}/2-ben-hidden.jpg", 'photo_consent_at' => null]);
    sweptPhoto("children/{$ccf->id}/1-amara.jpg", '2026-10-01 09:00');
    sweptPhoto("children/{$ccf->id}/2-ben-hidden.jpg", '2026-10-01 09:00');
    sweptPhoto("children/{$ccf->id}/1-amara-replaced.jpg", '2026-10-01 09:00');
    sweptPhoto("children/{$ccf->id}/3-uploading.jpg", '2026-11-03 20:30');
    sweptPhoto("children/{$other->id}/4-failed-save.jpg", '2026-11-01 09:00');

    sweep([])
        ->expectsOutputToContain('Deleted 2 photo files no child points at.')
        ->assertSuccessful();

    expect(Storage::disk('photos')->allFiles())->toEqualCanonicalizing([
        "children/{$ccf->id}/1-amara.jpg",
        "children/{$ccf->id}/2-ben-hidden.jpg",
        "children/{$ccf->id}/3-uploading.jpg",
    ]);
});

it('only lists them on a dry run @story#153', function (): void {
    $ccf = church(['slug' => 'ccf']);
    sweptPhoto("children/{$ccf->id}/1-orphan.jpg", '2026-10-01 09:00');

    sweep(['--dry-run' => true])
        ->expectsOutputToContain("children/{$ccf->id}/1-orphan.jpg")
        ->expectsOutputToContain('Would delete 1 photo file no child points at (dry run: nothing deleted).')
        ->assertSuccessful();

    expect(Storage::disk('photos')->allFiles())->toBe(["children/{$ccf->id}/1-orphan.jpg"]);
});

it('runs every day at 3 at night in the church\'s timezone', function (): void {
    $events = collect(app(Schedule::class)->events())->filter(fn (Event $e): bool => str_contains((string) $e->command, 'nxtgen:photos-sweep'));

    $timezone = $events->sole()->timezone;

    expect($events)->toHaveCount(1)
        ->and($events->sole()->expression)->toBe('0 3 * * *')
        ->and($timezone instanceof DateTimeZone ? $timezone->getName() : $timezone)->toBe(config('nxtgen.church.timezone'));
});
