<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

/*
 * The photos disk is private, and production puts it in an S3 bucket (PHOTOS_DISK=s3). Review 3 (round A/B):
 * the S3 adapter was not installed, so production would have failed on the first photo while every local test
 * (Storage::fake) stayed green. This builds the real S3 disk, without a network call.
 */

it('can build the S3 photos disk that production uses', function (): void {
    $disk = Storage::build([
        'driver' => 's3', 'key' => 'k', 'secret' => 's', 'region' => 'me-central-1', 'bucket' => 'nxtgen-photos', 'visibility' => 'private',
    ]);

    expect($disk->getAdapter())->toBeInstanceOf(AwsS3V3Adapter::class);
});

it('keeps photos private by default', function (): void {
    expect(config('filesystems.disks.photos.visibility'))->toBe('private')
        ->and(config('filesystems.disks.photos.root'))->toContain('app/private/photos');
});

it('uses the bucket Laravel Cloud attaches (AWS_* settings) when PHOTOS_DISK=s3 — even with a blank PHOTOS_BUCKET (review 4)', function (): void {
    $set = ['PHOTOS_DISK' => 's3', 'PHOTOS_BUCKET' => '', 'AWS_BUCKET' => 'cloud-bucket', 'AWS_ENDPOINT' => 'https://r2.example', 'AWS_USE_PATH_STYLE_ENDPOINT' => 'true'];
    foreach ($set as $k => $v) {
        putenv("{$k}={$v}");
        $_ENV[$k] = $v;
    }

    $photos = data_get(require config_path('filesystems.php'), 'disks.photos');

    foreach (array_keys($set) as $k) {
        putenv($k);
        unset($_ENV[$k]);
    }
    expect(data_get($photos, 'driver'))->toBe('s3')
        ->and(data_get($photos, 'bucket'))->toBe('cloud-bucket')
        ->and(data_get($photos, 'endpoint'))->toBe('https://r2.example')
        ->and(data_get($photos, 'use_path_style_endpoint'))->toBeTrue()
        ->and(data_get($photos, 'visibility'))->toBe('private');
});
