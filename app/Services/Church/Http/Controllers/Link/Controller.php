<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Link;

use App\Services\Church\Features\Link\AddLinkFeature;
use App\Services\Church\Features\Link\MoveLinkFeature;
use App\Services\Church\Features\Link\RemoveLinkFeature;
use App\Services\Church\Features\Link\ShowLinksFeature;
use App\Services\Church\Features\Link\UpdateLinkFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** Links (GLOSSARY): a satellite's own addresses on its QR poster. */
final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowLinksFeature::class);
    }

    public function store(): RedirectResponse
    {
        return $this->serve(AddLinkFeature::class);
    }

    public function update(string $church, int $link): RedirectResponse
    {
        return $this->serve(UpdateLinkFeature::class, ['linkId' => $link]);
    }

    public function move(string $church, int $link): RedirectResponse
    {
        return $this->serve(MoveLinkFeature::class, ['linkId' => $link]);
    }

    public function destroy(string $church, int $link): RedirectResponse
    {
        return $this->serve(RemoveLinkFeature::class, ['linkId' => $link]);
    }
}
