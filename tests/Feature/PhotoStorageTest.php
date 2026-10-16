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
