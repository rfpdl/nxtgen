<?php

declare(strict_types=1);

namespace App\Services\Auth\Data;

use Spatie\LaravelData\Data;

final class RequestMagicLinkInput extends Data
{
    public function __construct(
        public string $email,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }
}
