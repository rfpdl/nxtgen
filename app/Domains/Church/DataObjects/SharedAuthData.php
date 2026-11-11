<?php

declare(strict_types=1);

namespace App\Domains\Church\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Who is signed in, on every page (SharedPropsData): none for a visitor. */
#[TypeScript]
final class SharedAuthData extends Data
{
    public function __construct(
        /** @var array{id: int, name: string}|null */
        public ?array $user,
        /** More than one place to go (a parent who serves, or two churches) → the "Switch" link. */
        #[LiteralTypeScriptType('boolean')]
        public Lazy|bool $can_switch,
        /** The platform admin (spec item 12) → the bar's "Platform" link (Roy 2026-11-10). */
        public bool $is_platform_admin,
    ) {}
}
