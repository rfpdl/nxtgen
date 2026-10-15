<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

/**
 * A child's photo, taken with the phone camera or chosen from the gallery. `consent` is honoured ONLY when a
 * parent sends it — staff can never agree on a parent's behalf.
 */
final class ChildPhotoInput extends Data
{
    public function __construct(
        public UploadedFile $photo,
        public bool $consent = false,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360']];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'photo.required' => 'Please take or choose a photo.',
            'photo.image' => 'Please choose a photo (JPG, PNG or WebP).',
            'photo.mimes' => 'Please choose a photo (JPG, PNG or WebP).',
            'photo.max' => 'That photo is too large. Please choose one under 15 MB.',
        ];
    }
}
