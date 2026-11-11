<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\Church\DataObjects\GrantOptionData;
use App\Domains\Church\DataObjects\StaffMemberData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** People: everyone with a Role at this satellite, and every Role this admin may give (Church/People/Index) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class ChurchPeoplePageData extends Data
{
    public function __construct(
        /** @var list<StaffMemberData> */
        public array $people,
        /** @var list<GrantOptionData> */
        public array $grant_options,
    ) {}
}
