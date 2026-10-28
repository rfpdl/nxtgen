<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Closure;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** "Confirm head count" on Who's in: how many children the Squad leader counted, 0–200. */
#[TypeScript]
final class HeadCountInput extends Data
{
    private const string SAY = 'Please type a number from 0 to 200.';

    public function __construct(public int $count) {}

    /**
     * A whole number, never a yes/no: Laravel's `integer` reads a JSON `true` as 1.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        $notYesNo = function (string $attribute, mixed $value, Closure $fail): void {
            if (is_bool($value)) {
                $fail(self::SAY);
            }
        };

        return ['count' => ['required', $notYesNo, 'integer', 'min:0', 'max:200']];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return ['count.required' => self::SAY, 'count.integer' => self::SAY, 'count.min' => self::SAY, 'count.max' => self::SAY];
    }
}
