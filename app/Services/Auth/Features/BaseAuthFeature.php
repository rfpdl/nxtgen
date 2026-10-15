<?php

declare(strict_types=1);

namespace App\Services\Auth\Features;

use Lucid\Units\Feature;

/**
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BaseAuthFeature extends Feature
{
    protected const string LINK_FAILED = 'That link has expired or was already used. Enter your email to get a new one.';
}
