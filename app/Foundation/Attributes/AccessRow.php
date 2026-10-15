<?php

declare(strict_types=1);

namespace App\Foundation\Attributes;

use App\Enums\AccessRowEnum;
use Attribute;

/** The access-matrix row a Feature serves (plan §3.2). Required on every staff and parent Feature (AccessRowTest). */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AccessRow
{
    public function __construct(public AccessRowEnum $row) {}
}
