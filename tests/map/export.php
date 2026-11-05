<?php

declare(strict_types=1);

/*
 * The test map's data (TestMap::export) as JSON on stdout, for tests/map/serve.mjs. Boots the app for its routes only;
 * it reads no database.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Tests\Support\TestMap;

require __DIR__.'/../../vendor/autoload.php';
/** @var Application $app */
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo json_encode(TestMap::export(dirname(__DIR__, 2)), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
