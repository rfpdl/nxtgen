<?php

declare(strict_types=1);

return [
    /*
     * The church `php artisan db:seed` creates in production (ChurchSeeder) — settings, never code. Satellite
     * names are matched by the registry import's "Home satellite" column, so spell them as the sheet does.
     * Everyone else is added on People; families come from the import.
     */
    'church' => [
        'slug' => env('NXTGEN_CHURCH_SLUG', 'ccf'),
        'name' => env('NXTGEN_CHURCH_NAME', 'CCF'),
        'timezone' => env('NXTGEN_CHURCH_TIMEZONE', 'Asia/Dubai'),
        'satellites' => env('NXTGEN_SATELLITES', 'CCF Dxb'),   // Roy 2026-10-16: one satellite to start
        'first_admin_email' => env('NXTGEN_FIRST_ADMIN_EMAIL', 'blivejesus@gmail.com'),   // Roy 2026-10-16
        'first_admin_name' => env('NXTGEN_FIRST_ADMIN_NAME', 'Roy'),
    ],

    'rate_limits' => [
        /*
         * Public forms and emailed-link use, per device (IP) per minute. Production keeps 20. Only `.env.e2e`
         * raises it: three test browsers on one machine look like one device. Per-EMAIL limits (5 sign-in
         * requests a minute, 5 emails per 10 minutes) are fixed in code and never relaxed.
         */
        'per_device_per_minute' => (int) env('RATE_LIMIT_PER_DEVICE_PER_MINUTE', 20),
    ],
];
