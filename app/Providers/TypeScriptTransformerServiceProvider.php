<?php

declare(strict_types=1);

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

/**
 * Generates `resources/js/types/generated.d.ts` from every `#[TypeScript]` class and enum.
 *
 * Run `php artisan typescript:transform` after changing any Spatie Data class or `#[TypeScript]`
 * enum. The output is committed, so a clone typechecks without running PHP.
 *
 * The generated file declares a global `App.*` namespace, which is awkward to reference in a
 * component and impossible to auto-import — so `resources/js/types/index.ts` holds short importable
 * aliases over it, the same pattern palo uses. The aliases duplicate no definitions; change the PHP
 * and they follow.
 */
class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->transformer(AttributedClassTransformer::class)
            ->transformer(EnumTransformer::class)
            ->transformDirectories(app_path())
            // The directory and the filename are separate settings. Passing a path to the writer
            // resolves it against the DEFAULT output directory, which fails with
            // "Output directory resources/js/generated does not exist" — an error that names a
            // directory nobody asked for.
            ->outputDirectory('resources/js/types')
            ->writer(new GlobalNamespaceWriter('generated.d.ts'))
            ->formatter(PrettierFormatter::class);
    }
}
