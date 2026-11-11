<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\CheckIn\DataObjects\ServeClassData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Serve's first page: today, and its classes (Church/Serve/Index) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class ServePageData extends Data
{
    public function __construct(
        /** "Sat 31 Oct" */
        public string $today,
        /** @var list<ServeClassData> */
        public array $classes,
    ) {}
}
