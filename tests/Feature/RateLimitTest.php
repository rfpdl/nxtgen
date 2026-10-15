<?php

declare(strict_types=1);

use App\Data\Models\Organization;

use function Pest\Laravel\post;

/*
 * Per-device limits on public forms and emailed links: one setting (`nxtgen.rate_limits.per_device_per_minute`,
 * 20 unless the environment says otherwise). The browser suite raises it in `.env.e2e` only, because three
 * browsers on one machine are one "device"; the per-EMAIL limits are never relaxed anywhere.
 */

it('defaults to 20 a minute per device', function (): void {
    expect(config('nxtgen.rate_limits.per_device_per_minute'))->toBe(20);
});

it('refuses the registration form past the per-device limit', function (): void {
    Organization::factory()->create(['slug' => 'ccf']);
    config(['nxtgen.rate_limits.per_device_per_minute' => 2]);

    post('/ccf/register', [])->assertStatus(302);
    post('/ccf/register', [])->assertStatus(302);
    post('/ccf/register', [])->assertStatus(429);
});

it('refuses emailed-link use past the per-device limit', function (): void {
    config(['nxtgen.rate_limits.per_device_per_minute' => 1]);

    post('/magic/login/nope')->assertStatus(302);
    post('/magic/login/nope')->assertStatus(429);
});
