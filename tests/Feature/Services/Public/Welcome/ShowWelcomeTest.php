<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Tests\Support\church;

/** The `register_url` the welcome page was given. */
function welcomeRegisterUrl(): ?string
{
    $response = get('/')->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Welcome')->has('register_url'));
    $url = $response->inertiaProps('register_url');

    expect($url === null || is_string($url))->toBeTrue('register_url is a URL or null');

    return is_string($url) ? $url : null;
}

it('shows the welcome page to a visitor who is not signed in @story#1', function (): void {
    get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Welcome'));
});

it('links to the registration form of the church this install serves, and that form opens @story#1', function (): void {
    config()->set('nxtgen.church.slug', 'ccf');
    church(['slug' => 'ccf']);

    $url = welcomeRegisterUrl();

    expect($url)->toBeString();
    get((string) $url)->assertOk();
});

it('reads the configured slug in capitals as the church\'s own @story#1', function (): void {
    config()->set('nxtgen.church.slug', 'CCF');
    church(['slug' => 'ccf']);

    $url = welcomeRegisterUrl();

    expect($url)->toBeString();
    get((string) $url)->assertOk();
});

it('still opens, with no register link, when that church is not there (yet) @story#1', function (string $slug): void {
    config()->set('nxtgen.church.slug', $slug);

    expect(welcomeRegisterUrl())->toBeNull();
})->with(['missing church' => 'ccf', 'empty setting' => '']);
