<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Domains\Family\Jobs\CreateChildPhotoJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
