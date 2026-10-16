<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Data\TransformationContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\Transformed\Untransformable;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;

/**
 * Generates only the enums that say `#[TypeScript]` — every enum in `app/Enums` (EnumTest). A domain's private
 * enum (the registry import's column map) never reaches a page and stays out of `generated.d.ts`.
 */
final class AttributedEnumTransformer extends EnumTransformer
{
    public function transform(PhpClassNode $phpClassNode, TransformationContext $context): Transformed|Untransformable
    {
        if ($phpClassNode->getAttributes(TypeScript::class) === []) {
            return Untransformable::create();
        }

        return parent::transform($phpClassNode, $context);
    }
}
