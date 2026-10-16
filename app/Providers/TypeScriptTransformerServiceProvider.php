<?php

declare(strict_types=1);

namespace App\Providers;

use App\Foundation\Support\AttributedEnumTransformer;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptRaw;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

/**
 * Generates `resources/js/types/generated.d.ts` from every `#[TypeScript]` class and enum: the response DTOs a page
 * reads, the Inputs a page posts (unlike aegis, DECISIONS 2026-10-17) and the enums. Property names are written as
 * declared — DTOs declare them snake_case, so the page reads snake_case (DECISIONS 2026-10-17, as in aegis).
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
            // Only #[TypeScript] enums: a domain's private enum (RegistryCsvColumn) never reaches a page.
            ->transformer(AttributedEnumTransformer::class)
            ->transformDirectories(app_path())
            // An Input's uploaded photo is a browser File on the page that posts it (ChildPhotoInput).
            ->replaceType(UploadedFile::class, new TypeScriptRaw('File'))
            // The directory and the filename are separate settings. Passing a path to the writer
            // resolves it against the DEFAULT output directory, which fails with
            // "Output directory resources/js/generated does not exist" — an error that names a
            // directory nobody asked for.
            ->outputDirectory('resources/js/types')
            ->writer(new GlobalNamespaceWriter('generated.d.ts'))
            ->formatter(PrettierFormatter::class);
    }
}
