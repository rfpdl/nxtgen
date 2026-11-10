<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Session;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One of the satellite's classes open now, in the bar's dropdown on a class's page (Roy 2026-11-10): its door's code and name. */
#[TypeScript]
final class SessionChoiceData extends Data
{
    public function __construct(
        public string $code,
        /** "1PM" */
        public string $name,
    ) {}

    public static function fromModel(Session $session): self
    {
        return new self(code: $session->code, name: $session->schedule->name);
    }
}
