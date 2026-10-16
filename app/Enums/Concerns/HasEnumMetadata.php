<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * A backed enum that describes itself for people: a label and a semantic colour token — ported from aegis.
 *
 * Pair with {@see ProvidesEnumMetadata}, which builds toFrontend() from these two methods, so the enum is the one
 * place its wording lives (the page reads it through `useEnum()`).
 */
interface HasEnumMetadata
{
    public function label(): string;

    /** Semantic colour token: success | info | warning | danger | muted. */
    public function color(): string;
}
