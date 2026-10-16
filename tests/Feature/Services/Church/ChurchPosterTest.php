<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Card\Jobs\CreateQrCodeSvgJob;
use App\Enums\RoleEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atChurch;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/*
 * The printable registration poster (plan §6 R1 "printable QR poster"): a QR code that opens this church's
 * registration form, for the foyer. Any staff member can print it.
 */

it('gives a staff member a poster whose QR code opens this church\'s registration form', function (): void {
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));

    actingAs($user)->get('/ccf/poster')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Church/Poster')
        ->where('church.name', 'CCF')
        ->where('registerUrl', url('/ccf/register'))
        ->where('qr', fn (string $qr): bool => str_starts_with($qr, 'data:image/svg+xml;base64,')
            && str_contains((string) base64_decode(substr($qr, 26), true), '<svg')));
});

it('is not for a parent', function (): void {
    $church = church(['slug' => 'ccf']);
    $family = familyAt(satelliteOf($church));
    $guardian = User::factory()->create();
    guardianOf($family, $guardian);

    actingAs($guardian)->get('/ccf/poster')->assertNotFound();
});

it('draws a different code for a different address', function (): void {
    $a = (new CreateQrCodeSvgJob('https://example.test/ccf/register'))->handle();
    $b = (new CreateQrCodeSvgJob('https://example.test/other/register'))->handle();

    expect($a)->toStartWith('<?xml')->toContain('<svg')->and($a)->not->toBe($b);
});
