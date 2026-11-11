<?php

declare(strict_types=1);

namespace App\Services\Auth\DataObjects;

use App\Domains\Church\DataObjects\LandingOptionData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** "Where to?" after sign-in, for someone with more than one place (Auth/Choose) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class ChoosePageData extends Data
{
    public function __construct(
        /** @var list<LandingOptionData> */
        public array $options,
    ) {}
}
