<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\CardRequest;

use App\Services\Church\Features\CardRequest\ApproveCardRequestFeature;
use App\Services\Church\Features\CardRequest\FileCardRequestFeature;
use App\Services\Church\Features\CardRequest\GiveCardRequestFeature;
use App\Services\Church\Features\CardRequest\MarkApprovedCardRequestsPrintedFeature;
use App\Services\Church\Features\CardRequest\MarkCardRequestPaidFeature;
use App\Services\Church\Features\CardRequest\MarkCardRequestPrintedFeature;
use App\Services\Church\Features\CardRequest\RefuseCardRequestFeature;
use App\Services\Church\Features\CardRequest\ShowCardRequestsFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowCardRequestsFeature::class);
    }

    public function store(string $church, int $child): RedirectResponse
    {
        return $this->serve(FileCardRequestFeature::class, ['childId' => $child]);
    }

    public function markPaid(string $church, int $cardRequest): RedirectResponse
    {
        return $this->serve(MarkCardRequestPaidFeature::class, ['requestId' => $cardRequest]);
    }

    public function approve(string $church, int $cardRequest): RedirectResponse
    {
        return $this->serve(ApproveCardRequestFeature::class, ['requestId' => $cardRequest]);
    }

    public function refuse(string $church, int $cardRequest): RedirectResponse
    {
        return $this->serve(RefuseCardRequestFeature::class, ['requestId' => $cardRequest]);
    }

    public function markPrinted(string $church, int $cardRequest): RedirectResponse
    {
        return $this->serve(MarkCardRequestPrintedFeature::class, ['requestId' => $cardRequest]);
    }

    public function markAllPrinted(): RedirectResponse
    {
        return $this->serve(MarkApprovedCardRequestsPrintedFeature::class);
    }

    public function give(string $church, int $cardRequest): RedirectResponse
    {
        return $this->serve(GiveCardRequestFeature::class, ['requestId' => $cardRequest]);
    }
}
