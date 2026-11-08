<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Card\Jobs\CreateQrCodeSvgJob;
use App\Enums\RoleEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/*
 * The printable registration poster (plan §6 R1 "printable QR poster"): a QR code that opens this church's
 * registration form, for the foyer. Any staff member can print it.
 */

it('gives a staff member a poster whose QR code opens this church\'s registration form @story#80', function (): void {
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);
    $dubai = satelliteOf($church, ['name' => 'CCF Dubai', 'slug' => 'dubai', 'code' => 'k7m2q9xa']);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($dubai));

    // The poster is the address satellite's (DECISIONS 2026-11-08). Its QR carries the satellite's code, which never
    // changes, so a printed poster outlives an address change; the address printed under it is the one to type (spec
    // item 7).
    actingAs($user)->get('/dubai/serve/poster')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Church/Poster')
        ->where('church.name', 'CCF')
        ->where('register_url', url('/dubai/register'))
        ->where('qr_url', url('/k7m2q9xa/register'))
        // The image itself says the code address — drawn from it, not from the printed one (review, issue 07).
        ->where('qr', 'data:image/svg+xml;base64,'.base64_encode((new CreateQrCodeSvgJob(text: url('/k7m2q9xa/register')))->handle()))
        ->where('qr', fn (string $qr): bool => str_starts_with($qr, 'data:image/svg+xml;base64,')
            && str_contains((string) base64_decode(substr($qr, 26), true), '<svg')));
});

it('is not found for a Guardian with no Role in the church @story#81', function (): void {
    $church = church(['slug' => 'ccf']);
    $family = familyAt(satelliteOf($church, ['slug' => 'dubai']));
    $guardian = User::factory()->create();
    guardianOf($family, $guardian);

    actingAs($guardian)->get('/dubai/serve/poster')->assertNotFound();
});

it('draws a different code for a different address', function (): void {
    $a = (new CreateQrCodeSvgJob('https://example.test/ccf/register'))->handle();
    $b = (new CreateQrCodeSvgJob('https://example.test/other/register'))->handle();

    expect($a)->toStartWith('<?xml')->toContain('<svg')->and($a)->not->toBe($b);
});
