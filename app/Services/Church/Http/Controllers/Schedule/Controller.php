<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Schedule;

use App\Services\Church\Features\Schedule\AddScheduleFeature;
use App\Services\Church\Features\Schedule\RetireScheduleFeature;
use App\Services\Church\Features\Schedule\ShowSchedulesFeature;
use App\Services\Church\Features\Schedule\UpdateScheduleFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** Classes (GLOSSARY "Schedule"): the address says "classes", the people's word; the code says Schedule. */
final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowSchedulesFeature::class);
    }

    public function store(): RedirectResponse
    {
        return $this->serve(AddScheduleFeature::class);
    }

    public function update(string $church, int $schedule): RedirectResponse
    {
        return $this->serve(UpdateScheduleFeature::class, ['scheduleId' => $schedule]);
    }

    public function retire(string $church, int $schedule): RedirectResponse
    {
        return $this->serve(RetireScheduleFeature::class, ['scheduleId' => $schedule]);
    }
}
