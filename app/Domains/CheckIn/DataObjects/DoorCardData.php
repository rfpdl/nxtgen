<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Family;
use App\Foundation\Attributes\NonClientFacing;
use Illuminate\Database\Eloquent\Collection;

/**
 * A door search read as a name card (GetDoorCardJob): the families of the children it named, the card for the page,
 * and whether the ordinary search should run as well (a typed card that matched no child — it may be a surname).
 */
#[NonClientFacing]
final readonly class DoorCardData
{
    /** @param Collection<int, Family> $families */
    public function __construct(
        public Collection $families,
        public DoorScanData $scan,
        public bool $searchToo,
    ) {}
}
