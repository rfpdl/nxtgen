<?php

declare(strict_types=1);

namespace App\Domains\Auth\DataObjects;

use App\Data\Models\MagicLink;
use App\Foundation\Attributes\NonClientFacing;

/** The plain token exists only here, long enough to put it in an email. It is never stored. */
#[NonClientFacing]
final readonly class IssuedMagicLinkData
{
    public function __construct(
        public string $token,
        public MagicLink $link,
    ) {}
}
