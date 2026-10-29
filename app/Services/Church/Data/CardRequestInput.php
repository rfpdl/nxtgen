<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Enums\CardReasonEnum;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * "New card…" on a child's card: Lost or Damaged, and what staff were told. A first card is never filed by hand. The
 * note arrives trimmed, and a blank one as null, by the app's TrimStrings + ConvertEmptyStringsToNull middleware.
 */
#[TypeScript]
final class CardRequestInput extends Data
{
    public function __construct(
        public CardReasonEnum $reason,
        public ?string $note = null,
    ) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(CardReasonEnum::class)->only(CardReasonEnum::staffChoices())],
            'note' => ['nullable', 'string', 'max:200'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'reason.required' => 'Please choose Lost or Damaged.',
            'reason.enum' => 'Please choose Lost or Damaged.',
            'note.max' => 'Please keep the note to 200 letters.',
        ];
    }
}
