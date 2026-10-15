<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;
use App\Services\Auth\Providers\AuthServiceProvider;
use App\Services\Family\Providers\FamilyServiceProvider;
use App\Services\Org\Providers\OrgServiceProvider;
use App\Services\Public\Providers\PublicServiceProvider;

return [
    AppServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,

    AuthServiceProvider::class,
    FamilyServiceProvider::class,
    PublicServiceProvider::class,

    // LAST: its routes start with /{organization}, so every fixed top-level path (/login, /home, /magic…)
    // must be registered before it or the church wildcard would swallow them.
    OrgServiceProvider::class,
];
