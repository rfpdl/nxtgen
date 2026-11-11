<?php

declare(strict_types=1);

namespace App\Services\Auth\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The backup password page (Auth/Password, plan D7) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class PasswordPageData extends Data
{
    public function __construct(
        public bool $has_password,
    ) {}
}
