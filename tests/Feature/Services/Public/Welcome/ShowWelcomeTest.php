<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

it('shows the welcome page to a visitor who is not signed in @story#1', function (): void {
    get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Welcome'));
});
