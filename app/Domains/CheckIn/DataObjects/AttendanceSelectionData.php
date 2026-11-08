<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Schedule;
use App\Foundation\Attributes\NonClientFacing;
use Carbon\CarbonImmutable;

/** The Session Attendance shows (GetAttendanceSelectionJob): a class at the address satellite — null when there is none — and a date. */
#[NonClientFacing]
final readonly class AttendanceSelectionData
{
    public function __construct(
        public ?Schedule $schedule,
        public CarbonImmutable $date,
    ) {}
}
