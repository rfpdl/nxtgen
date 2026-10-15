<?php

declare(strict_types=1);

namespace App\Services\Org\Data;

use Spatie\LaravelData\Data;

final class CorrectFamilyEmailInput extends Data
{
    public function __construct(public string $email) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:255', 'unique:users,email']];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return ['email.unique' => 'Someone else already uses this email.'];
    }
}
