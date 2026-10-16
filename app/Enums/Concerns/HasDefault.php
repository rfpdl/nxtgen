<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * A backed enum that declares its canonical default case — ported from aegis. The model's default, the form's
 * starting value and the factory all read this one method, so "the default status" lives in one place.
 */
interface HasDefault
{
    public static function default(): self;
}
