<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Helpers are plain FUNCTIONS in the `Tests\Support` namespace, and PSR-4 autoloads classes, not
// functions — so each helper file is required once here. Palo does the same; the alternative is a
// composer `files` entry per file, which is the same list somewhere less obvious.
require_once __DIR__.'/Support/InertiaHelpers.php';
require_once __DIR__.'/Support/FormPayloads.php';
require_once __DIR__.'/Support/World.php';
require_once __DIR__.'/Architecture/Helpers.php';

/*
|--------------------------------------------------------------------------
| Test case binding
|--------------------------------------------------------------------------
|
| `tests/Unit` gets NO application and NO database — see the "Unit" testsuite in
| phpunit.xml. Anything that needs either belongs in Feature.
|
| Helpers live in `tests/Support/*.php` under the `Tests\Support` namespace and
| are imported per file with `use function`, rather than being defined here as
| globals. Two reasons: an import says where a helper came from, and PHPStan can
| follow it.
|
| Tests use Pest's FUNCTIONAL api — `use function Pest\Laravel\get; get('/x')` —
| not `$this->get('/x')`. Inside a closure, `$this` is a PendingCalls\TestCall to
| static analysis, so every `$this->get()` reads as an undefined method and the
| suite cannot pass level 8. The functions are typed. This is palo's convention
| and it exists for exactly that reason.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../app/Domains');

pest()->extend(TestCase::class)->in('Architecture');
