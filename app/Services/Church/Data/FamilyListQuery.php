<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Domains\Family\DataObjects\FamilyListFiltersData;
use App\Enums\ChildStatusEnum;
use Illuminate\Http\Request;

/**
 * The list's filters as they arrive in the address (`?q=&satellite=&status=&level=&unconfirmed=1&duplicates=1`)
 * — read leniently: anything unrecognised is simply no filter. Shared by the list and its Excel download, so
 * the download is what the screen shows.
 */
final class FamilyListQuery
{
    public static function from(Request $request): FamilyListFiltersData
    {
        $q = $request->string('q')->trim()->value();

        return new FamilyListFiltersData(
            query: $q === '' ? null : $q,
            satelliteId: $request->integer('satellite') ?: null,
            status: ChildStatusEnum::tryFrom($request->string('status')->value()),
            level: $request->integer('level') ?: null,
            unconfirmedOnly: $request->boolean('unconfirmed'),
            duplicatesOnly: $request->boolean('duplicates'),
        );
    }

    /** @return array{q: string, satellite: int|null, status: string|null, level: int|null, unconfirmed: bool, duplicates: bool} */
    public static function toProps(FamilyListFiltersData $f): array
    {
        return [
            'q' => $f->query ?? '',
            'satellite' => $f->satelliteId,
            'status' => $f->status?->value,
            'level' => $f->level,
            'unconfirmed' => $f->unconfirmedOnly,
            'duplicates' => $f->duplicatesOnly,
        ];
    }
}
