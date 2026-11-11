<?php

declare(strict_types=1);

namespace App\Services\Platform\DataObjects;

use App\Domains\Church\DataObjects\ChurchSettingsData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The church's settings (Platform/Settings), and the time zones to pick from — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class PlatformSettingsPageData extends Data
{
    public function __construct(
        public ChurchSettingsData $settings,
        /** @var list<string> */
        public array $timezones,
    ) {}
}
