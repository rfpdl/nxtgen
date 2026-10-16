<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Foundation\Jobs\BaseJob;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Store a child's photo on the private `photos` disk (plan §5), replacing any earlier one. Adapted from aegis's
 * avatar flow. The image is RE-ENCODED, never stored as uploaded: turned upright from the phone's orientation
 * tag, shrunk to 600px, saved as JPEG — which also drops every EXIF field, including where it was taken.
 * The image is prepared first; then the child is LOCKED (the caller runs this in a transaction) and consent
 * re-checked before the file is written, so an upload racing a withdrawal can neither leave a photo behind nor be
 * swept away half-saved. Returns whether the photo was kept.
 *
 * @extends BaseJob<bool>
 */
final class CreateChildPhotoJob extends BaseJob
{
    private const int MAX_SIDE = 600;

    public function __construct(
        private readonly Child $child,
        private readonly UploadedFile $file,
    ) {}

    public function handle(): bool
    {
        $image = @imagecreatefromstring((string) file_get_contents($this->file->getRealPath()));
        if (! $image instanceof GdImage) {
            throw new RuntimeException('That file is not a photo we can read.');
        }

        $image = $this->shrink($this->upright($image));
        ob_start();
        imagejpeg($image, null, 82);
        $jpeg = (string) ob_get_clean();

        $path = "children/{$this->child->organization_id}/{$this->child->id}-".Str::random(16).'.jpg';

        // Lock and check consent BEFORE anything is stored: a withdrawal (which also locks) can then never sweep up
        // an upload that is still on its way, and nothing is written for a child whose consent is gone (review 3).
        $locked = Child::query()->where('organization_id', $this->child->organization_id)->lockForUpdate()->findOrFail($this->child->id);
        if ($locked->photo_consent_at === null) {
            return false;
        }

        Storage::disk('photos')->put($path, $jpeg);
        $old = $locked->photo_path;
        try {
            $locked->forceFill(['photo_path' => $path])->save();
        } catch (Throwable $e) {
            Storage::disk('photos')->delete($path);   // nothing points at it — do not keep it

            throw $e;
        }
        $this->child->setRawAttributes($locked->getAttributes(), true);

        // The old file goes only once the new pointer is committed; if that delete fails, the file is an orphan that
        // consent withdrawal still removes (DeleteChildPhotoJob deletes every file of the child).
        if ($old !== null && $old !== $path) {
            DB::afterCommit(function () use ($old): void {
                try {
                    Storage::disk('photos')->delete($old);
                } catch (Throwable $e) {
                    report($e);
                }
            });
        }

        return true;
    }

    /** Phones store photos sideways and tag how to turn them; re-encoding loses the tag, so turn them now. */
    private function upright(GdImage $image): GdImage
    {
        $exif = @exif_read_data($this->file->getRealPath());
        $orientation = is_array($exif) && is_int($exif['Orientation'] ?? null) ? $exif['Orientation'] : 1;

        $turned = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $turned instanceof GdImage ? $turned : $image;
    }

    private function shrink(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        if ($scale >= 1) {
            return $image;
        }

        $small = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

        return $small instanceof GdImage ? $small : $image;
    }
}
