<?php

declare(strict_types=1);

namespace App\Services\Auth\DataObjects;

use App\Enums\MagicLinkRouteEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The page an emailed link opens (Auth/ConfirmLink): its purpose, its words (MagicLinkRouteEnum) and where the button posts — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class ConfirmLinkPageData extends Data
{
    public function __construct(
        public MagicLinkRouteEnum $purpose,
        public string $title,
        public string $prompt,
        public string $button,
        public string $action,
    ) {}
}
