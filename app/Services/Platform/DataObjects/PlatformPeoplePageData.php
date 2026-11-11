<?php

declare(strict_types=1);

namespace App\Services\Platform\DataObjects;

use App\Domains\Church\DataObjects\GrantOptionData;
use App\Domains\Church\DataObjects\StaffMemberData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The platform admin's People: everyone with a Role in the church, and every Role they may give (Platform/People) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class PlatformPeoplePageData extends Data
{
    public function __construct(
        /** @var list<StaffMemberData> */
        public array $people,
        /** @var list<GrantOptionData> */
        public array $grant_options,
    ) {}
}
