<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\User;
use App\Domains\Family\Jobs\CreateChildPhotoJob;
use App\Domains\Family\Jobs\UpdateChildPhotoConsentJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Tests\Support\atTheSameTime;
use function Tests\Support\withCommittedWorld;

/*
 * A child's photo as it is stored (plan §5: private storage). A phone photo arrives large, sideways (EXIF
 * orientation) and carrying where it was taken (GPS). What is kept is small, upright, and says nothing else.
 */

function phonePhoto(): UploadedFile
{
    return new UploadedFile(base_path('tests/Fixtures/phone-photo-rotated-gps.jpg'), 'IMG_0001.jpg', 'image/jpeg', null, true);
}

it('stores the photo upright, small, as a JPEG with no location or camera data', function (): void {
    Storage::fake('photos');
    $child = Child::factory()->create(['photo_consent_at' => now()]);

    (new CreateChildPhotoJob(child: $child, file: phonePhoto()))->handle();

    $path = (string) $child->fresh()?->photo_path;
    Storage::disk('photos')->assertExists($path);
    $bytes = Storage::disk('photos')->get($path) ?? '';
    [$width, $height, $type] = getimagesizefromstring($bytes) ?: [0, 0, 0];
    $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes)) ?: [];

    expect($type)->toBe(IMAGETYPE_JPEG)
        ->and($height)->toBeGreaterThan($width)           // the 300×200 original was tagged "rotate 90°"
        ->and($exif)->not->toHaveKey('GPSLatitude')
        ->and($exif)->not->toHaveKey('Make');
});

it('replaces an earlier photo and removes the old file', function (): void {
    Storage::fake('photos');
    $child = Child::factory()->create(['photo_consent_at' => now()]);

    (new CreateChildPhotoJob(child: $child, file: phonePhoto()))->handle();
    $first = (string) $child->fresh()?->photo_path;
    (new CreateChildPhotoJob(child: $child->fresh() ?? $child, file: phonePhoto()))->handle();

    Storage::disk('photos')->assertMissing($first);
    Storage::disk('photos')->assertExists((string) $child->fresh()?->photo_path);
});

it('keeps nothing when consent is gone at the moment of saving (an upload racing a withdrawal)', function (): void {
    Storage::fake('photos');
    $child = Child::factory()->create(['photo_consent_at' => null]);

    $kept = (new CreateChildPhotoJob(child: $child, file: phonePhoto()))->handle();

    expect($kept)->toBeFalse()
        ->and($child->fresh()?->photo_path)->toBeNull()
        ->and(Storage::disk('photos')->allFiles())->toBe([]);
});

it('keeps nothing when the parent\'s withdrawal is still saving as the upload arrives — two phones, two connections', function (): void {
    Storage::fake('photos');
    $child = Child::factory()->create(['photo_consent_at' => now()]);
    $parent = User::factory()->create();

    withCommittedWorld(function () use ($child, $parent): void {
        [$committedAt, $upload] = atTheSameTime(
            // The withdrawal has written but not yet committed when the upload reads the child.
            function () use ($child, $parent): float {
                DB::transaction(function () use ($child, $parent): void {
                    (new UpdateChildPhotoConsentJob(child: $child, given: false, by: $parent))->handle();
                    usleep(1_000_000);
                });

                return microtime(true);
            },
            function () use ($child): array {
                usleep(150_000);
                $startedAt = microtime(true);

                return [$startedAt, DB::transaction(fn (): bool => (new CreateChildPhotoJob(child: $child, file: phonePhoto()))->handle())];
            },
        );
        // Each answer comes back from its own process, untyped.
        if (! is_float($committedAt) || ! is_array($upload) || ! is_float($upload[0] ?? null)) {
            throw new UnexpectedValueException('The two phones did not answer.');
        }
        [$uploadStartedAt, $kept] = $upload;

        // The race really happened: the upload began while the withdrawal was still open (else this proves nothing).
        expect($uploadStartedAt)->toBeLessThan($committedAt)
            ->and($kept)->toBeFalse()
            ->and($child->fresh()?->photo_path)->toBeNull()
            ->and(Storage::disk('photos')->allFiles())->toBe([]);
    });
});
