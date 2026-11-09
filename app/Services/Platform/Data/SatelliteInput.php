<?php

declare(strict_types=1);

namespace App\Services\Platform\Data;

use App\Foundation\Support\RouteSatellite;
use App\Foundation\Support\SatelliteCode;
use Closure;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A satellite as the platform admin names it (spec item 12): its name ("CCF Al Ain") and its address (`al-ain`). An
 * address is plain lowercase, nobody else's, never a word the app uses itself, and never shaped like a QR code — an
 * address is looked up before a code (Astra, issue 07).
 */
#[TypeScript]
final class SatelliteInput extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        $satellite = request()->route('satellite');

        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => [
                'required', 'string', 'max:40', 'regex:/^'.RouteSatellite::pattern().'$/',
                // Words joined by single dashes: no dash at either end, never two together.
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('satellites', 'slug')->ignore(is_object($satellite) && isset($satellite->id) ? $satellite->id : null),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && SatelliteCode::looksLikeOne($value)) {
                        $fail('That looks like a QR code. Please choose a word, like "al-ain".');
                    }
                },
            ],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'name.required' => 'Please type the satellite\'s name, e.g. "CCF Al Ain".',
            'slug.required' => 'Please type its address, e.g. "al-ain".',
            'slug.regex' => 'Use small letters, numbers and dashes only — and not a word the app uses itself.',
            'slug.unique' => 'Another satellite has that address.',
        ];
    }
}
