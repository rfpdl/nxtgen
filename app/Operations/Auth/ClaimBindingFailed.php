<?php

declare(strict_types=1);

namespace App\Operations\Auth;

use RuntimeException;

/** Thrown inside the claim transaction to roll it back when the login no longer matches the claim. */
final class ClaimBindingFailed extends RuntimeException {}
