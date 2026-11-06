<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Adding a Link: which satellite's poster, then the same fields and rules as changing one (SatelliteLinkInput).
 * Whether the viewer may add there is AccessScope::canManageLinksAt.
 */
#[TypeScript]
final class AddSatelliteLinkInput extends Data
{
    public function __construct(
        public int $satellite_id,
        public string $label,
        public string $url,
    ) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return ['satellite_id' => ['required', 'integer'], ...SatelliteLinkInput::rules()];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return SatelliteLinkInput::messages();
    }
}
