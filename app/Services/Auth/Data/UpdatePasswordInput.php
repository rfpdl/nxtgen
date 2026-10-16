<?php

declare(strict_types=1);

namespace App\Services\Auth\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UpdatePasswordInput extends Data
{
    public function __construct(#[\SensitiveParameter] public string $password) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['password' => ['required', 'string', 'min:10', 'max:200', 'confirmed']];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'password.min' => 'Please use at least 10 characters — a short sentence is easiest to remember.',
            'password.confirmed' => 'The two passwords are not the same.',
        ];
    }
}
