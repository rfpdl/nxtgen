<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Domains\Family\DataObjects\ChildListFiltersData;
use App\Enums\ChildStatusEnum;
use Illuminate\Http\Request;

/** The children list's filters as they arrive in the address (`?q=&satellite=&status=&level=`), read leniently. */
final class ChildListQuery
{
    public static function from(Request $request): ChildListFiltersData
    {
        $q = $request->string('q')->trim()->value();

        return new ChildListFiltersData(
            query: $q === '' ? null : $q,
            satelliteId: $request->integer('satellite') ?: null,
            status: ChildStatusEnum::tryFrom($request->string('status')->value()),
            level: $request->integer('level') ?: null,
        );
    }

    /** @return array{q: string, satellite: int|null, status: string|null, level: int|null, unconfirmed: bool, duplicates: bool} */
    public static function toProps(ChildListFiltersData $f): array
    {
        // Same shape as the families list's filters, so one filter bar serves both lists.
        return ['q' => $f->query ?? '', 'satellite' => $f->satelliteId, 'status' => $f->status?->value, 'level' => $f->level, 'unconfirmed' => false, 'duplicates' => false];
    }
}
