<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A Link's label and address (GLOSSARY "Link"), as an admin types them. Only an https address: the poster prints it
 * as a QR code parents scan, so never a plain-http or script address.
 */
#[TypeScript]
final class SatelliteLinkInput extends Data
{
    public function __construct(
        public string $label,
        public string $url,
    ) {}

    /**
     * Shared with AddSatelliteLinkInput.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:60'],
            'url' => ['required', 'string', 'max:500', 'starts_with:https://', 'url:https'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'label.required' => 'Please type what the poster should say, e.g. "Parents\' WhatsApp group".',
            'label.max' => 'Please keep it to 60 letters.',
            'url.required' => 'Please paste the link.',
            'url.starts_with' => 'Please paste the whole link, starting with https://',
            'url.url' => 'Please paste the whole link, starting with https://',
            'url.max' => 'That link is too long.',
        ];
    }
}
