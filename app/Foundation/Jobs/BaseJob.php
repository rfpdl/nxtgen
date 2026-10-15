<?php

declare(strict_types=1);

namespace App\Foundation\Jobs;

use Lucid\Units\Job;

/**
 * Every Job extends this and declares what handle() returns, e.g. `@extends BaseJob<Family>`, so
 * `$this->run(...)` is typed at the call site (stubs/Lucid.stub).
 *
 * @template-covariant TResult
 *
 * @extends Job<TResult>
 */
abstract class BaseJob extends Job {}
