<?php

declare(strict_types=1);

return [
    'rate_limits' => [
        /*
         * Public forms and emailed-link use, per device (IP) per minute. Production keeps 20. Only `.env.e2e`
         * raises it: three test browsers on one machine look like one device. Per-EMAIL limits (5 sign-in
         * requests a minute, 5 emails per 10 minutes) are fixed in code and never relaxed.
         */
        'per_device_per_minute' => (int) env('RATE_LIMIT_PER_DEVICE_PER_MINUTE', 20),
    ],
];
