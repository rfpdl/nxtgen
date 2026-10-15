<?php

declare(strict_types=1);

namespace App\Services\Public\Features;

use Lucid\Units\Feature;

/**
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BasePublicFeature extends Feature {}
