<?php

declare(strict_types=1);

use App\Enums\RoleEnum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * A satellite's registration code (spec item 7, Roy 2026-11-08): the printed QR poster carries `/{code}/register`, which
 * forwards to the satellite's own address — so a poster keeps working when a platform admin changes the address. Only
 * the registration page takes a code; anywhere else it is an address nobody has.
 */

it('forwards a code\'s registration page to the satellite\'s address, keeping what the QR carried @story#2', function (): void {
    satelliteOf(church(), ['slug' => 'dubai', 'code' => 'k7m2q9xa']);

    get('/k7m2q9xa/register')->assertRedirect('/dubai/register');
    get('/k7m2q9xa/register?utm_source=poster')->assertRedirect('/dubai/register?utm_source=poster');
    get('/dubai/register')->assertOk();
});

it('forwards an old poster\'s code to the address the satellite has now @story#2', function (): void {
    $dubai = satelliteOf(church(), ['slug' => 'dubai', 'code' => 'k7m2q9xa']);
    $dubai->forceFill(['slug' => 'dubai-north'])->save();

    get('/k7m2q9xa/register')->assertRedirect('/dubai-north/register');
    get('/dubai/register')->assertNotFound();
});

it('takes a code only on the registration page — a staff page or a form post at a code is not found', function (): void {
    $dubai = satelliteOf(church(), ['slug' => 'dubai', 'code' => 'k7m2q9xa']);
    $admin = staffMember(RoleEnum::Admin, atSatellite($dubai));

    actingAs($admin)->get('/k7m2q9xa/admin/families')->assertNotFound();
    actingAs($admin)->get('/k7m2q9xa')->assertNotFound();
    post('/k7m2q9xa/register', [])->assertNotFound();
    get('/k7m2q9xa/register/thanks')->assertNotFound();
});
