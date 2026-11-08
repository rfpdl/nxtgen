<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\CardRequest;

use App\Data\Models\CardRequest;
use App\Data\Models\Child;
use App\Data\Models\PrintList;
use App\Services\Church\Features\CardRequest\ApproveCardRequestFeature;
use App\Services\Church\Features\CardRequest\ExportPrintListFeature;
use App\Services\Church\Features\CardRequest\FileCardRequestFeature;
use App\Services\Church\Features\CardRequest\GiveCardRequestFeature;
use App\Services\Church\Features\CardRequest\MakePrintListFeature;
use App\Services\Church\Features\CardRequest\MarkCardRequestPaidFeature;
use App\Services\Church\Features\CardRequest\MarkCardRequestPrintedFeature;
use App\Services\Church\Features\CardRequest\MarkPrintListPrintedFeature;
use App\Services\Church\Features\CardRequest\RefuseCardRequestFeature;
use App\Services\Church\Features\CardRequest\ShowCardRequestsFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowCardRequestsFeature::class);
    }

    public function makePrintList(): RedirectResponse
    {
        return $this->serve(MakePrintListFeature::class);
    }

    public function downloadPrintList(PrintList $printList): BinaryFileResponse
    {
        return $this->serve(ExportPrintListFeature::class, ['printList' => $printList]);
    }

    public function markPrintListPrinted(PrintList $printList): RedirectResponse
    {
        return $this->serve(MarkPrintListPrintedFeature::class, ['printList' => $printList]);
    }

    public function store(Child $child): RedirectResponse
    {
        return $this->serve(FileCardRequestFeature::class, ['child' => $child]);
    }

    public function markPaid(CardRequest $cardRequest): RedirectResponse
    {
        return $this->serve(MarkCardRequestPaidFeature::class, ['cardRequest' => $cardRequest]);
    }

    public function approve(CardRequest $cardRequest): RedirectResponse
    {
        return $this->serve(ApproveCardRequestFeature::class, ['cardRequest' => $cardRequest]);
    }

    public function refuse(CardRequest $cardRequest): RedirectResponse
    {
        return $this->serve(RefuseCardRequestFeature::class, ['cardRequest' => $cardRequest]);
    }

    public function markPrinted(CardRequest $cardRequest): RedirectResponse
    {
        return $this->serve(MarkCardRequestPrintedFeature::class, ['cardRequest' => $cardRequest]);
    }

    public function give(CardRequest $cardRequest): RedirectResponse
    {
        return $this->serve(GiveCardRequestFeature::class, ['cardRequest' => $cardRequest]);
    }
}
