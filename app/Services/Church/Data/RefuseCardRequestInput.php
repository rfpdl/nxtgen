<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** "Refuse" on Cards: a short reason, required (DECISIONS 2026-10-29) — trimmed by the app's middleware. */
#[TypeScript]
final class RefuseCardRequestInput extends Data
{
    public function __construct(public string $reason) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:200']];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'reason.required' => 'Please say why, in a few words.',
            'reason.max' => 'Please keep the reason to 200 letters.',
        ];
    }
}
