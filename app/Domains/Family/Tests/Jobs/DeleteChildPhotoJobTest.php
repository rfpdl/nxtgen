<?php

declare(strict_types=1);

use App\Data\Models\Note;
use App\Data\Models\User;
use App\Domains\Family\Jobs\DeleteChildPhotoJob;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* Delete a child's photo for good (TODO #25): the file and the pointer, noted on the child's history. */

it('deletes the stored file, clears the photo and notes who did it — consent stays as it was', function (): void {
    Storage::fake('photos');
    $child = childOf(familyAt(satelliteOf(church())), ['nickname' => 'Amara', 'photo_path' => 'children/1/a.jpg', 'photo_consent_at' => now()]);
    Storage::disk('photos')->put('children/1/a.jpg', 'jpeg');
    $janice = User::factory()->create(['name' => 'Janice']);

    expect((new DeleteChildPhotoJob(child: $child, by: $janice))->handle())->toBeTrue();

    Storage::disk('photos')->assertMissing('children/1/a.jpg');
    expect([$child->fresh()?->photo_path, $child->fresh()?->photo_consent_at !== null])->toBe([null, true])
        ->and(Note::query()->latest('id')->value('body'))->toBe('Amara: photo deleted for good by Janice.');
});

it('says when there was no photo, and notes nothing', function (): void {
    Storage::fake('photos');
    $child = childOf(familyAt(satelliteOf(church())), ['photo_path' => null]);

    expect((new DeleteChildPhotoJob(child: $child, by: User::factory()->create()))->handle())->toBeFalse()
        ->and(Note::query()->count())->toBe(0);
});

it('deletes the file only once the change is saved; a file that will not delete still leaves the photo gone and noted (Astra)', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['nickname' => 'Amara', 'photo_path' => 'children/1/a.jpg', 'photo_consent_at' => now()]);
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('delete')->once()->with('children/1/a.jpg')->andThrow(new RuntimeException('The bucket is down.'));
    Storage::shouldReceive('disk')->with('photos')->andReturn($disk);
    $janice = User::factory()->create(['name' => 'Janice']);

    DB::transaction(fn () => (new DeleteChildPhotoJob(child: $child, by: $janice))->handle());

    expect($child->fresh()?->photo_path)->toBeNull()
        ->and(Note::query()->latest('id')->value('body'))->toBe('Amara: photo deleted for good by Janice.');
});

it('keeps the file when the change is not saved', function (): void {
    Storage::fake('photos');
    Storage::disk('photos')->put('children/1/a.jpg', 'jpeg');
    $child = childOf(familyAt(satelliteOf(church())), ['photo_path' => 'children/1/a.jpg']);

    try {
        DB::transaction(function () use ($child): void {
            (new DeleteChildPhotoJob(child: $child, by: User::factory()->create()))->handle();

            throw new RuntimeException('The save failed.');
        });
    } catch (RuntimeException) {
    }

    Storage::disk('photos')->assertExists('children/1/a.jpg');
    expect($child->fresh()?->photo_path)->toBe('children/1/a.jpg');
});
