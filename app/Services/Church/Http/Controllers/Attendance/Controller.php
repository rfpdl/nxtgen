<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Attendance;

use App\Services\Church\Features\Attendance\ExportAttendanceFeature;
use App\Services\Church\Features\Attendance\ShowAttendanceFeature;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Attendance (admins only — DECISIONS 2026-10-26): a Session's children by Squad, and the same as Excel. */
final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowAttendanceFeature::class);
    }

    public function export(): BinaryFileResponse
    {
        return $this->serve(ExportAttendanceFeature::class);
    }
}
