<?php

declare(strict_types=1);

namespace App\Services\Console\Features;

use Lucid\Units\Feature;

/**
 * Features served from an Artisan command (Lucid's `ServesFeatures`, as aegis serves its mailbox catch-up) rather
 * than from a route. The operator at the terminal is the one served, so there is no signed-in user, church in the
 * address or AccessScope: the command passes what the Feature works on.
 *
 * @template-covariant TResult
 *
 * @extends Feature<TResult>
 */
abstract class BaseConsoleFeature extends Feature {}
