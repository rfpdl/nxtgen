<?php

declare(strict_types=1);

namespace App\Services\Auth\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PasswordLoginInput extends Data
{
    public function __construct(
        public string $email,
        #[\SensitiveParameter] public string $password,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['email' => ['required', 'string', 'max:255'], 'password' => ['required', 'string', 'max:200']];
    }
}
