<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

/*
 * The welcome page (Roy 2026-11-08): empty for now — the bar with Sign in. A family registers at its satellite's own
 * address (`/dubai/register`, from that satellite's QR code or link), so the page links to no form.
 */

it('shows the welcome page to a visitor who is not signed in, with no registration form to link to @story#1', function (): void {
    get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Welcome')->missing('register_url'));
});
