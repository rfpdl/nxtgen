<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Domains\Family\DataObjects\ChildListFiltersData;
use App\Enums\ChildStatusEnum;
use App\Foundation\Support\LevelChart;
use Illuminate\Http\Request;

/** The children list's filters as they arrive in the address (`?q=&status=&level=`), read leniently. */
final class ChildListQuery
{
    public static function from(Request $request): ChildListFiltersData
    {
        $q = $request->string('q')->trim()->value();

        return new ChildListFiltersData(
            query: $q === '' ? null : $q,
            status: ChildStatusEnum::tryFrom($request->string('status')->value()),
            level: $request->string('level')->value() === (string) LevelChart::NONE ? LevelChart::NONE : ($request->integer('level') ?: null),
        );
    }
}
