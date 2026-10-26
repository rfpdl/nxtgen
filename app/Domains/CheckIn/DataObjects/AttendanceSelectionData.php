<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Schedule;
use App\Foundation\Attributes\NonClientFacing;
use Carbon\CarbonImmutable;

/** The Session Attendance shows (GetAttendanceSelectionJob): a satellite, its class, a date — null when there is none. */
#[NonClientFacing]
final readonly class AttendanceSelectionData
{
    public function __construct(
        public ?int $satelliteId,
        public ?Schedule $schedule,
        public CarbonImmutable $date,
    ) {}
}
