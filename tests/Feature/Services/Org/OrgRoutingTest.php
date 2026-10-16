<?php

declare(strict_types=1);

use App\Data\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\church;

/*
 * The church's pages live at the top of the URL (/ccf/families), so the church slug is a wildcard
 * that could swallow every fixed path. These pin the order in bootstrap/providers.php.
 */

it('keeps the fixed top-level pages reachable', function (string $path, int $status): void {
    get($path)->assertStatus($status);
})->with([
    'login' => ['/login', 200],
    'health' => ['/up', 200],
    'home needs a login' => ['/home', 302],
]);

it('serves the church pages without /admin in the address', function (): void {
    $org = church(['slug' => 'ccf']);

    get('/ccf/families')->assertRedirect('/login');
    get('/ccf/admin/families')->assertNotFound();

    actingAs(User::factory()->create());
    // Someone with no role in this church sees nothing there at all (RequireAssignment answers 404).
    get('/ccf/families/new')->assertNotFound();
    expect(route('org.families.register', ['organization' => $org->slug], false))->toBe('/ccf/families/new');
});
