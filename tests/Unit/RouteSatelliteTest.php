<?php

declare(strict_types=1);

use App\Foundation\Support\RouteSatellite;

/*
 * A satellite's address is the first part of every staff and registration address (`/dubai/…`), so the app's own
 * top-level pages can never be one: `/login`, `/family`, `/platform`… would be swallowed by a satellite.
 */

it('takes a plain lowercase address', function (string $address): void {
    expect(preg_match('/^'.RouteSatellite::pattern().'$/', $address))->toBe(1);
})->with(['dubai', 'abu-dhabi', 'k7m2q9xa']);

it('never takes an address the app uses itself, or one that is not plain', function (string $address): void {
    expect(preg_match('/^'.RouteSatellite::pattern().'$/', $address))->toBe(0);
})->with([...RouteSatellite::RESERVED, 'Dubai', '-dubai', 'abu dhabi', '']);
