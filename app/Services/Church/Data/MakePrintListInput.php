<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Foundation\Support\SearchWords;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** "Make print list" on Cards' Approved tab: the child-name search the tab shows (none = every approved card). */
#[TypeScript]
final class MakePrintListInput extends Data
{
    public function __construct(public ?string $q = null) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return ['q' => ['nullable', 'string']];
    }

    /** The search as Cards' list keeps it (SearchWords::tidy), so the list made is the list shown. */
    public function search(): ?string
    {
        return SearchWords::tidy($this->q);
    }
}
