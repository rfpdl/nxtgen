<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What happened to one child of the last Moving-Up when an admin put it back (DECISIONS 2026-11-06): back where they
 * were, already put back, or left alone — someone changed their Level or status since, and that change stands.
 */
#[TypeScript]
enum PutBackResultEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case PutBack = 'put_back';
    case AlreadyPutBack = 'already_put_back';
    case ChangedSince = 'changed_since';

    /** Said after the child's name: "Ben was already put back". */
    public function label(): string
    {
        return match ($this) {
            self::PutBack => 'is put back',
            self::AlreadyPutBack => 'was already put back',
            self::ChangedSince => 'has a different Level or status since Moving-Up, so was left as they are',
        };
    }

    public function color(): string
    {
        return $this === self::ChangedSince ? 'danger' : 'success';
    }
}
